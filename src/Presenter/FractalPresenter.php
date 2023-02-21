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
use League\Fractal\Serializer\Serializer;
use League\Fractal\Serializer\SerializerAbstract;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SaltId\LumenRepository\Contracts\PresenterInterface;
use SaltId\LumenRepository\Transformers\TransformerAbstract;

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

    /** @var Item|Collection|null $resource */
    protected Item|Collection|null $resource = null;

    /** @var Request $request */
    protected Request $request;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct()
    {
        $this->fractalManager = app(Manager::class);
        $this->request = app('request');
        $this->parseIncludes();
        $this->setupSerializer();
    }

    /**
     * @return static
     */
    protected function setupSerializer(): static
    {
        $serializer = $this->serializer();

        if ($serializer instanceof SerializerAbstract) {
            $this->fractalManager->setSerializer(new $serializer());
        }

        return $this;
    }

    /**
     * @return Serializer
     */
    public function serializer(): Serializer
    {
        $serializer = config('repository.fractal.serializer', 'League\\Fractal\\Serializer\\ArraySerializer');

        return new $serializer();
    }

    /**
     * @return static
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function parseIncludes(): static
    {
        $paramIncludes = config('repository.fractal.params.include', 'include');

        if ($this->request->has($paramIncludes)) {
            $this->fractalManager->parseIncludes($this->request->get($paramIncludes));
        }

        return $this;
    }

    /**
     * @return TransformerAbstract|null
     */
    abstract public function getTransformer(): ?TransformerAbstract;

    /** @inheritDoc */
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

    /**
     * @param $data
     *
     * @return Collection
     */
    protected function transformCollection($data): Collection
    {
        return new Collection($data, $this->getTransformer(), $this->resourceKeyCollection);
    }

    /**
     * @param $data
     *
     * @return Item
     */
    protected function transformItem($data): Item
    {
        return new Item($data, $this->getTransformer(), $this->resourceKeyItem);
    }

    /**
     * @param $paginator
     *
     * @return Collection
     */
    protected function transformPaginator($paginator): Collection
    {
        $collection = $paginator->getCollection();

        $resource = new Collection($collection, $this->getTransformer(), $this->resourceKeyCollection);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return $resource;
    }
}
