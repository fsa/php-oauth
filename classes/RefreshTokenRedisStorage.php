<?php

namespace FSA\OAuth;

class RefreshTokenRedisStorage extends AbstractRedis implements RefreshTokenStorageInterface
{

    public function set($token, $data, int $expired_in): void
    {
        $this->redis()->setEx($this->prefix . ':OAuth:RefreshToken:' . $token, $expired_in, json_encode($data));
    }

    public function get($token): ?object
    {
        $token_info = json_decode($this->redis()->get($this->prefix . ':OAuth:RefreshToken:' . $token));
        return $token_info ? $token_info : null;
    }
}
