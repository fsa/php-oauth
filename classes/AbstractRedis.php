<?php

namespace FSA\OAuth;

use Redis;

abstract class AbstractRedis
{
    private Redis $redis;
    private callable $redis_callback;

    public function __construct(Redis|callable $redis, protected string $prefix)
    {
        if ($redis instanceof Redis) {
            $this->redis = $redis;
        } else {
            $this->redis_callback = $redis;
        }
        $this->prefix = $prefix;
    }

    protected function redis()
    {
        if (is_null($this->redis)) {
            $this->redis = ($this->redis_callback)();
        }
        return $this->redis;
    }
}
