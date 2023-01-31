<?php

namespace FSA\OAuth;

use Redis;

class AuthorizationCodeRedisStorage implements AuthorizationCodeStorageInterface
{

    public function __construct(
        private Redis $redis,
        private string $name
    ) {
    }

    public function set($code, $data, $expired_in): void
    {
        $this->redis->setEx($this->name . ':OAuth:Code:' . $code, $expired_in, json_encode($data));
    }

    public function get($code): ?object
    {
        $code_info = json_decode($this->redis->get($this->name . ':OAuth:Code:' . $code));
        $this->redis->del($this->name . ':OAuth:Code:' . $code);
        return $code_info ? $code_info : null;
    }
}
