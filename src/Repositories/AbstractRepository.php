<?php

namespace SaltId\LumenRepository\Repositories;

use Illuminate\Database\Eloquent\{Collection, Model, Builder};
use SaltId\LumenRepository\Contracts\{CriteriaInterface,
    PresenterInterface,
    RepositoryCriteriaInterface,
    RepositoryInterface};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     * @var PresenterInterface $presenter
     */
    protected PresenterInterface $presenter;

    /**
     * @var bool $skipPresenter
     */
    protected bool $skipPresenter = false;

    /**
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->criteria = new Collection();
        $this->makePresenter();

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
    public function all(array $columns = ['*']): array|Collection
    {
        $this->applyCriteria();

        $result = $this->model instanceof Builder ? $this->model->get($columns) : $this->model::all($columns);

        return $this->parserResult($result);
    }

    /** @inheritDoc */
    public function paginate(int $limit = 5, array $columns = ['*'])
    {
        $this->applyCriteria();

        $result = $this->model->paginate($limit, $columns)->appends(['limit' => $limit]);

        return $this->parserResult($result);
    }

    /** @inheritDoc */
    public function first(array $columns = ['*'])
    {
        $this->applyCriteria();

        $result = $this->model->first($columns);

        return $this->parserResult($result);
    }

    /** @inheritDoc */
    public function last(array $columns = ['*'])
    {
        $this->applyCriteria();

        $result = $this->model->latest('id')->first($columns);

        return $this->parserResult($result);
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

        return $this->parserResult($model);
    }

    /** @inheritDoc */
    public function findByField(string $field, int|array|string|null $value, array $columns = ['*'])
    {
        $this->applyCriteria();

        $result = $this->model->where($field, '=', $value)->get($columns);

        return $this->parserResult($result);
    }

    /** @inheritDoc */
    public function findWhere(array $where, array $columns = ['*'], ?int $limit = null)
    {
        $this->applyCriteria();

        $result = $this->model->where($where)->get($columns);

        return $this->parserResult($result);
    }

    /** @inheritDoc */
    public function findWhereIn(string $field, array $values, array $columns = ['*'])
    {
        $this->applyCriteria();

        $result = $this->model->whereIn($field, $values)->get($columns);

        return $this->parserResult($result);
    }

    /** @inheritDoc */
    public function findWhereNotIn(string $field, array $where, array $columns = ['*'])
    {
        $this->applyCriteria();

        $result = $this->model->whereNotIn($field, $where)->get($columns);

        return $this->parserResult($result);
    }

    /** @inheritDoc */
    public function findWhereBetween(string $field, array $where, array $columns = ['*'])
    {
        $this->applyCriteria();

        $result = $this->model->whereBetween($field, $where)->get($columns);

        return $this->parserResult($result);
    }

    /** @inheritDoc */
    public function create(array $attributes)
    {
        $result = $this->model->create($attributes);

        return $this->parserResult($result);
    }

    /**
     * @inheritDoc
     * @throws ModelNotFoundException
     */
    public function delete(int $id)
    {
        $model = $this->find($id);

        if (!$model) {
            throw new ModelNotFoundException();
        }

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
        $model = $this->model->find($id);

        if (!$model) {
            throw new ModelNotFoundException();
        }

        $model->update($attributes);

        return $this->parserResult($model);
    }

    /** @inheritDoc */
    public function orderBy(string $column, string $direction = 'ASC'): static
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

    /**
     * @return PresenterInterface
     */
    abstract public function presenter(): PresenterInterface;

    /**
     * @param string|PresenterInterface|null $presenter
     * @return PresenterInterface|null
     */
    public function makePresenter(null|string|PresenterInterface $presenter = null): ?PresenterInterface
    {
        $presenter = !is_null($presenter) ? $presenter : $this->presenter();

        if (is_null($presenter)) return null;

        $this->presenter = is_string($presenter) ? app($presenter) : $presenter;

        return $this->presenter;
    }

    /**
     * @param bool $status
     * @return $this
     */
    public function skipPresenter(bool $status = true): static
    {
        $this->skipPresenter = $status;

        return $this;
    }

    /**
     * @param $result
     */
    public function parserResult($result)
    {
        if ($this->skipPresenter) {
            return $result;
        }

        return $this->presenter->present($result);
    }
}
