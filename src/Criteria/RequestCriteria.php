<?php

namespace SaltId\LumenRepository\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use SaltId\LumenRepository\Contracts\RepositoryInterface;

class RequestCriteria extends AbstractCriteria
{
    /**
     * @param Builder|Model $model
     * @param RepositoryInterface $repository
     *
     * @return Builder|Model
     */
    public function apply(Builder|Model $model, RepositoryInterface $repository): Builder|Model
    {
        $search = $this->request->get('search');
        $searchFields = $this->request->get('searchFields', []);
        $filter = $this->request->get('filter');
        $orderBy = $this->request->get('orderBy');
        $sortedBy = $this->request->get('sortedBy');
        $with = $this->request->get('with');
        $withCount = $this->request->get('withCount');
        $searchJoin = $this->request->get('searchJoin');
        $sortedBy = !empty($sortedBy) ? $sortedBy : 'asc';
        $searchableFields = $repository->getSearchableFields();

        if ($search && count($searchableFields)) {
            $searchFields = is_array($searchFields) || is_null($searchFields) ? $searchFields : explode(';', $searchFields);
            $searchData = $this->parserSearchData($search);
            $fields = $this->parserFieldsSearch($searchableFields, $searchFields, array_keys($searchData));
            $search = $this->parserSearchValue($search);
            $modelForceAndWhere = strtolower($searchJoin) === 'and';

            $model = $model->where(function ($query) use ($fields, $search, $searchData, $modelForceAndWhere) {
                $isFirstField = true;
                /** @var Builder $query */

                foreach ($fields as $field => $condition) {

                    if (is_numeric($field)) {
                        $field = $condition;
                        $condition = "=";
                    }

                    $value = null;

                    $condition = trim(strtolower($condition));

                    if (isset($searchData[$field])) {
                        $value = ($condition == 'like' || $condition == 'ilike') ? "%{$searchData[$field]}%" : $searchData[$field];
                    } else {
                        if (!is_null($search) && !in_array($condition,['in','between'])) {
                            $value = ($condition == 'like' || $condition == 'ilike') ? "%{$search}%" : $search;
                        }
                    }

                    $relation = null;
                    if (stripos($field, '.')) {
                        $explode = explode('.', $field);
                        $field = array_pop($explode);
                        $relation = implode('.', $explode);
                    }
                    if ($condition === 'in') {
                        $value = explode(',',$value);
                        if( trim($value[0]) === "" || $field == $value[0]) {
                            $value = null;
                        }
                    }
                    if ($condition === 'between') {
                        $value = explode(',',$value);
                        if(count($value) < 2) {
                            $value = null;
                        }
                    }
                    $modelTableName = $query->getModel()->getTable();

                    if (!Schema::hasColumn($modelTableName, $field)) continue;

                    if ($isFirstField || $modelForceAndWhere) {
                        if (!is_null($value)) {
                            if (!is_null($relation)) {
                                $query->whereHas($relation, function($query) use($field,$condition,$value) {
                                    if ($condition === 'in') {
                                        $query->whereIn($field,$value);
                                    } elseif ($condition === 'between') {
                                        $query->whereBetween($field,$value);
                                    } else {
                                        $query->where($field,$condition,$value);
                                    }
                                });
                            } else {
                                if($condition === 'in') {
                                    $query->whereIn($modelTableName.'.'.$field,$value);
                                } elseif ($condition === 'between') {
                                    $query->whereBetween($modelTableName.'.'.$field,$value);
                                } else {
                                    $query->where($modelTableName.'.'.$field,$condition,$value);
                                }
                            }
                            $isFirstField = false;
                        }
                    } else {
                        if (!is_null($value)) {
                            if (!is_null($relation)) {
                                $query->orWhereHas($relation, function($query) use($field,$condition,$value) {
                                    if ($condition === 'in') {
                                        $query->whereIn($field,$value);
                                    } elseif ($condition === 'between') {
                                        $query->whereBetween($field, $value);
                                    } else {
                                        $query->where($field,$condition,$value);
                                    }
                                });
                            } else {
                                if ($condition === 'in') {
                                    $query->orWhereIn($modelTableName.'.'.$field, $value);
                                } elseif ($condition === 'between') {
                                    $query->whereBetween($modelTableName.'.'.$field,$value);
                                } else {
                                    $query->orWhere($modelTableName.'.'.$field, $condition, $value);
                                }
                            }
                        }
                    }
                }
            });
        }

        if (isset($orderBy) && !empty($orderBy)) {
            $orderBySplit = explode(';', $orderBy);
            if (count($orderBySplit) > 1) {
                $sortedBySplit = explode(';', $sortedBy);
                foreach ($orderBySplit as $orderBySplitItemKey => $orderBySplitItem) {
                    $sortedBy = $sortedBySplit[$orderBySplitItemKey] ?? $sortedBySplit[0];
                    $model = $this->parserFieldsOrderBy($model, $orderBySplitItem, $sortedBy);
                }
            } else {
                $model = $this->parserFieldsOrderBy($model, $orderBySplit[0], $sortedBy);
            }
        }

        if (isset($filter) && !empty($filter)) {
            if (is_string($filter)) {
                $filter = explode(';', $filter);
            }

            $model = $model->select($filter);
        }

        if ($with) {
            $with = explode(';', $with);
            $model = $model->with($with);
        }

        if ($withCount) {
            $withCount = explode(';', $withCount);
            $model = $model->withCount($withCount);
        }

        return $model;
    }

    protected function parserFieldsOrderBy($model, $orderBy, $sortedBy)
    {
        $split = explode('|', $orderBy);

        if(count($split) > 1) {
            $table = $model->getModel()->getTable();
            $sortTable = $split[0];
            $sortColumn = $split[1];

            $split = explode(':', $sortTable);
            $localKey = '.id';

            $keyName = null;
            if (count($split) > 1) {
                $sortTable = $split[0];

                $commaExp = explode(',', $split[1]);
                $keyName = $table.'.'.$split[1];
                if (count($commaExp) > 1) {
                    $keyName = $table.'.'.$commaExp[0];
                    $localKey = '.'.$commaExp[1];
                }
            }

            if (count($split) < 1) {
                $prefix = Str::singular($sortTable);
                $keyName = $table.'.'.$prefix.'_id';
            }

            $model = $model
                ->leftJoin($sortTable, $keyName, '=', $sortTable.$localKey)
                ->orderBy($sortColumn, $sortedBy)
                ->addSelect($table.'.*');
        }

        if(count($split) <= 1) {
            $model = $model->orderBy($orderBy, $sortedBy);
        }

        return $model;
    }

    protected function parserSearchData($search): array
    {
        $searchData = [];

        if (!stripos($search, ':')) return $searchData;

        $fields = explode(';', $search);

        foreach ($fields as $row) {
            try {
                list($field, $value) = explode(':', $row);
                $searchData[$field] = $value;
            } catch (\Exception $e) {
                //Surround offset error
            }
        }

        return $searchData;
    }

    protected function parserSearchValue($search): ?string
    {
        if (stripos($search, ';') || stripos($search, ':')) {
            $values = explode(';', $search);
            foreach ($values as $value) {
                $s = explode(':', $value);
                if (count($s) === 1) {
                    return $s[0];
                }
            }

            return null;
        }

        return $search;
    }

    protected function parserFieldsSearch(array $fields = [], array $searchFields = null, array $dataKeys = null): array
    {
        if (!is_null($searchFields) && count($searchFields)) {
            $acceptedConditions = [
                '=',
                'like'
            ];
            $originalFields = $fields;
            $fields = [];

            foreach ($searchFields as $index => $field) {
                $field_parts = explode(':', $field);
                $temporaryIndex = array_search($field_parts[0], $originalFields);

                if (count($field_parts) === 2) {
                    if (in_array($field_parts[1], $acceptedConditions)) {
                        unset($originalFields[$temporaryIndex]);
                        $field = $field_parts[0];
                        $condition = $field_parts[1];
                        $originalFields[$field] = $condition;
                        $searchFields[$index] = $field;
                    }
                }
            }

            if (!is_null($dataKeys) && count($dataKeys)) {
                $searchFields = array_unique(array_merge($dataKeys, $searchFields));
            }

            foreach ($originalFields as $field => $condition) {
                if (is_numeric($field)) {
                    $field = $condition;
                    $condition = '=';
                }
                if (in_array($field, $searchFields)) {
                    $fields[$field] = $condition;
                }
            }
        }

        return $fields;
    }
}
