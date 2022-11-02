<?php

namespace frontend\controllers;

use yii\filters\RateLimitInterface;

class IpLimiter implements RateLimitInterface
{
    public $allowance;
    public $allowance_updated_at;

    public function getRateLimit($request, $action)
    {
        return [5, 60];
    }

    public function loadAllowance($request, $action)
    {
        //[100-$count, time()];
        $request->getUserIP();
        return [$this->allowance, $this->allowance_updated_at];
    }

    public function saveAllowance($request, $action, $allowance, $timestamp)
    {
        // TODO: Implement saveAllowance() method.
    }
}