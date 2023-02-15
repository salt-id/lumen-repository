<?php

namespace SaltId\LumenRepository\Contracts;

use Illuminate\Database\Eloquent\Model;

interface CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param Model $model
     * @param RepositoryInterface $repository
     *
     */
    public function apply(Model $model, RepositoryInterface $repository);
}
