<?php

namespace SaltId\LumenRepository\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Http\Request;
use SaltId\LumenRepository\Contracts\CriteriaInterface;
use SaltId\LumenRepository\Contracts\RepositoryInterface;

abstract class AbstractCriteria implements CriteriaInterface
{
    protected Request $request;

    public function __construct()
    {
        $this->request = app('request');
    }

    public function apply(Model $model, RepositoryInterface $repository): Builder|Model
    {
        return $model;
    }
}
