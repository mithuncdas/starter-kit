<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Authenticate the next test request with a fresh Sanctum bearer token,
     * flushing any cached auth guard state from the previous request.
     */
    protected function asToken(string $token): self
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $this->withToken($token);
    }
}
