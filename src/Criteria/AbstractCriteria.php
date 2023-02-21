<?php

namespace SaltId\LumenRepository\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Http\Request;
use SaltId\LumenRepository\Contracts\CriteriaInterface;
use SaltId\LumenRepository\Contracts\RepositoryInterface;

abstract class AbstractCriteria implements CriteriaInterface
{
    /** @var Request $request */
    protected Request $request;

    public function __construct()
    {
        $this->request = Request::createFromGlobals();
    }

    /** @inheritDoc */
    public function apply(Builder|Model $model, RepositoryInterface $repository): Builder|Model
    {
        return $model;
    }
}
