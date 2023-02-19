<?php

namespace SaltId\LumenRepository\Contracts;

interface PresenterInterface
{
    /**
     * Prepare data to present
     *
     * @param $data
     *
     */
    public function present($data);
}
