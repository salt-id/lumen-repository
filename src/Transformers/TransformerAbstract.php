<?php

namespace SaltId\LumenRepository\Transformers;

use League\Fractal\TransformerAbstract as BaseTransformerAbstract;
use SaltId\LumenRepository\Contracts\TransformableInterface;

abstract class TransformerAbstract extends BaseTransformerAbstract
{
    /**
     *
     * @param TransformableInterface $transformable
     *
     * @return array
     */
    public function transform(TransformableInterface $transformable): array
    {
        return $transformable->transform();
    }
}
