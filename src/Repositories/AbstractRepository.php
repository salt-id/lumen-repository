<?php

namespace SaltId\LumenRepository\Repositories;

use Illuminate\Database\Eloquent\{Collection, Model, Builder};
use SaltId\LumenRepository\Contracts\{CriteriaInterface, RepositoryCriteriaInterface, RepositoryInterface};
use SaltId\LumenRepository\Criteria\RequestCriteria;
use SaltId\LumenRepository\Exceptions\ModelNotFoundException;

abstract class AbstractRepository implements RepositoryInterface, RepositoryCriteriaInterface
{
    /**
     * @var Builder|Model $model
     */
    protected Builder|Model $model;

    /**
     * @var Collection $criteria
     */
    protected Collection $criteria;

    /**
     * @var bool $skipCriteria
     */
    protected bool $skipCriteria = false;

    /**
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->criteria = new Collection();

        $this->boot();
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $criteria = $this->getCriteria() ?? [];

        foreach ($criteria as $criterion) {
            if (!class_exists($criterion)) {
                continue;
            }

            $criteriaInstance = new $criterion();

            if (!$criteriaInstance instanceof CriteriaInterface) {
                continue;
            }

            $this->pushCriteria($criteriaInstance);
        }

        $this->pushCriteria(new RequestCriteria());
    }

    /**
     * Retrieve model
     *
     * @return Model|Builder
     */
    public function getModel(): Model|Builder
    {
        return $this->model;
    }

    /** @inheritDoc */
    public function all(array $columns = ['*']): Collection
    {
        $this->applyCriteria();

        return $this->model instanceof Builder ? $this->model->get($columns) : $this->model::all($columns);
    }

    /** @inheritDoc */
    public function paginate(int $limit = 5, array $columns = ['*'])
    {
        $this->applyCriteria();

        return $this->model->paginate($limit, $columns)->appends(['limit' => $limit]);
    }

    /** @inheritDoc */
    public function first(array $columns = ['*'])
    {
        $this->applyCriteria();

        return $this->model->first($columns);
    }

    /** @inheritDoc */
    public function last(array $columns = ['*'])
    {
        $this->applyCriteria();

        return $this->model->latest('id')->first($columns);
    }

    /**
     * @inheritDoc
     * @throws ModelNotFoundException
     */
    public function find(int $id, array $columns = ['*'])
    {
        $this->applyCriteria();

        $model = $this->model->find($id, $columns);

        if (!$model) {
            throw new ModelNotFoundException();
        }

        return $model;
    }

    /** @inheritDoc */
    public function findByField(string $field, int|array|string|null $value, array $columns = ['*'])
    {
        $this->applyCriteria();

        return $this->model->where($field, '=', $value)->get($columns);
    }

    /** @inheritDoc */
    public function findWhere(array $where, array $columns = ['*'], ?int $limit = null)
    {
        $this->applyCriteria();

        return $this->model->where($where)->get($columns);
    }

    /** @inheritDoc */
    public function findWhereIn(string $field, array $values, array $columns = ['*'])
    {
        $this->applyCriteria();

        return $this->model->whereIn($field, $values)->get($columns);
    }

    /** @inheritDoc */
    public function findWhereNotIn(string $field, array $where, array $columns = ['*'])
    {
        $this->applyCriteria();

        return $this->model->whereNotIn($field, $where)->get($columns);
    }

    /** @inheritDoc */
    public function findWhereBetween(string $field, array $where, array $columns = ['*'])
    {
        $this->applyCriteria();

        return $this->model->whereBetween($field, $where)->get($columns);
    }

    /** @inheritDoc */
    public function create(array $attributes)
    {
        return $this->model->create($attributes);
    }

    /**
     * @inheritDoc
     * @throws ModelNotFoundException
     */
    public function delete(int $id)
    {
        $model = $this->find($id);

        $model->delete();

        return $model;
    }

    public function deleteWhere(array $where)
    {
        return $this->model->where($where)->delete();
    }

    /**
     * @inheritDoc
     * @throws ModelNotFoundException
     */
    public function update(array $attributes, int $id)
    {
        $model = $this->find($id);

        $model->update($attributes);

        return $model;
    }

    /** @inheritDoc */
    public function orderBy(string $column, string $direction = 'ASC')
    {
        $this->model = $this->model->orderBy($column, $direction);

        return $this;
    }

    /** @inheritDoc */
    public function applyCriteria(): static
    {
        if ($this->skipCriteria === true) {
            return $this;
        }

        $criteria = $this->getCriteria() ?? [];

        if (!$criteria) {
            return $this;
        }

        foreach ($criteria as $criterion) {
            if ($criterion instanceof CriteriaInterface) {
                $this->model = $criterion->apply($this->model, $this);
            }
        }

        return $this;
    }

    /** @inheritDoc */
    public function pushCriteria(CriteriaInterface $criteria): static
    {
        $this->criteria->push($criteria);

        return $this;
    }

    /** @inheritDoc */
    public function popCriteria(CriteriaInterface $criteria): static
    {
        $this->criteria = $this->criteria->reject(function ($item) use ($criteria) {
            if (is_object($item) && is_string($criteria)) {
                return get_class($item) === $criteria;
            }

            if (is_string($item) && is_object($criteria)) {
                return $item === get_class($criteria);
            }

            return get_class($item) === get_class($criteria);
        });

        return $this;
    }

    /** @inheritDoc */
    public function getCriteria(): Collection
    {
        return $this->criteria;
    }

    /** @inheritDoc */
    public function getByCriteria(CriteriaInterface $criteria)
    {
        $this->model = $criteria->apply($this->model, $this);

        return $this->model->get();
    }

    /** @inheritDoc */
    public function skipCriteria(bool $status = true): static
    {
        $this->skipCriteria = $status;

        return $this;
    }

    /** @inheritDoc */
    public function resetCriteria(): static
    {
        $this->criteria = new Collection();

        return $this;
    }
}
