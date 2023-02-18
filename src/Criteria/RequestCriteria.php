<?php

namespace SaltId\LumenRepository\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SaltId\LumenRepository\Contracts\RepositoryInterface;

class RequestCriteria extends AbstractCriteria
{
    public function apply(Model $model, RepositoryInterface $repository): Builder|Model
    {
        return $model;
    }
}
