<?php

namespace SaltId\LumenRepository\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param Builder|Model $model
     * @param RepositoryInterface $repository
     *
     * @return Builder|Model
     */
    public function apply(Builder|Model $model, RepositoryInterface $repository): Builder|Model;
}
