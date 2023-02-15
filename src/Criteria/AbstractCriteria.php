<?php

namespace SaltId\LumenRepository\Criteria;

use Illuminate\Database\Eloquent\Model;
use SaltId\LumenRepository\Contracts\CriteriaInterface;
use SaltId\LumenRepository\Contracts\RepositoryInterface;

abstract class AbstractCriteria implements CriteriaInterface
{
    public function apply(Model $model, RepositoryInterface $repository): Model
    {
        return $model;
    }
}
