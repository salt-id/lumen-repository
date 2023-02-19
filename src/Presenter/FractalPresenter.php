<?php

namespace SaltId\LumenRepository\Presenter;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\SerializerAbstract;
use SaltId\LumenRepository\Contracts\PresenterInterface;

abstract class FractalPresenter implements PresenterInterface
{
    /**
     * @var string|null $resourceKeyItem
     */
    protected ?string $resourceKeyItem = null;

    /**
     * @var string|null $resourceKeyCollection
     */
    protected ?string $resourceKeyCollection = null;

    /**
     * @var Manager $fractalManager
     */
    protected Manager $fractalManager;

    protected Item|Collection|null $resource = null;

    protected Request $request;

    public function __construct()
    {
        $this->fractalManager = app(Manager::class);
        $this->request = app('request');
        $this->parseIncludes();
        $this->setupSerializer();
    }

    protected function setupSerializer(): static
    {
        $serializer = $this->serializer();

        if ($serializer instanceof SerializerAbstract) {
            $this->fractalManager->setSerializer(new $serializer());
        }

        return $this;
    }

    public function serializer()
    {
        $serializer = config('repository.fractal.serializer', 'League\\Fractal\\Serializer\\ArraySerializer');

        return new $serializer();
    }

    protected function parseIncludes(): static
    {
        $paramIncludes = config('repository.fractal.params.include', 'include');

        if ($this->request->has($paramIncludes)) {
            $this->fractalManager->parseIncludes($this->request->get($paramIncludes));
        }

        return $this;
    }

    abstract public function getTransformer();

    public function present($data): ?array
    {
        if ($data instanceof Model) {
            $this->resource = $this->transformItem($data);
        }

        if ($data instanceof EloquentCollection) {
            $this->resource = $this->transformCollection($data);
        }

        if ($data instanceof LengthAwarePaginator) {
            $this->resource = $this->transformPaginator($data);
        }

        return $this->fractalManager
            ->createData($this->resource)
            ->toArray();
    }

    protected function transformCollection($data): Collection
    {;
        return new Collection($data, $this->getTransformer(), $this->resourceKeyCollection);
    }

    protected function transformItem($data): Item
    {
        return new Item($data, $this->getTransformer(), $this->resourceKeyItem);
    }

    protected function transformPaginator($paginator): Collection
    {
        $collection = $paginator->getCollection();

        $resource = new Collection($collection, $this->getTransformer(), $this->resourceKeyCollection);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return $resource;
    }
}
