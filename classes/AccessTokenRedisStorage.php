<?php

namespace FSA\OAuth;

use Redis;

class AccessTokenRedisStorage implements AccessTokenStorageInterface
{

    public function __construct(
        private Redis $redis,
        private string $name
    ) {
    }

    public function set($token, $data, $expired_in): void
    {
        $this->redis->setEx($this->name . ':OAuth:AccessToken:' . $token, $expired_in, json_encode($data));
    }

    public function get($token): ?object
    {
        $token_info = json_decode($this->redis->get($this->name . ':OAuth:AccessToken:' . $token));
        return $token_info ? $token_info : null;
    }
}
