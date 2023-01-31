<?php

namespace FSA\OAuth;

use Redis;

class RefreshTokenRedisStorage implements RefreshTokenStorageInterface
{

    public function __construct(
        private Redis $redis,
        private string $name
    ) {
    }

    public function set($token, $data, int $expired_in): void
    {
        $this->redis->setEx($this->name . ':OAuth:RefreshToken:' . $token, $expired_in, json_encode($data));
    }

    public function get($token): ?object
    {
        $token_info = json_decode($this->redis->get($this->name . ':OAuth:RefreshToken:' . $token));
        return $token_info ? $token_info : null;
    }
}
