<?php

namespace SaltId\LumenRepository\Contracts;

interface TransformableInterface
{
    /**
     * Transform model.
     *
     * @return array
     */
    public function transform(): array;
}
