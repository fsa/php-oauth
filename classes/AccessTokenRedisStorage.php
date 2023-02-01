<?php

namespace FSA\OAuth;

class AccessTokenRedisStorage extends AbstractRedis implements AccessTokenStorageInterface
{

    public function set($token, $data, $expired_in): void
    {
        $this->redis()->setEx($this->prefix . ':OAuth:AccessToken:' . $token, $expired_in, json_encode($data));
    }

    public function get($token): ?object
    {
        $token_info = json_decode($this->redis()->get($this->prefix . ':OAuth:AccessToken:' . $token));
        return $token_info ? $token_info : null;
    }
}
