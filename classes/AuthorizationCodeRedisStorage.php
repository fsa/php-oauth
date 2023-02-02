<?php

namespace FSA\OAuth;

class AuthorizationCodeRedisStorage extends AbstractRedis implements TokenStorageInterface
{

    public function set($code, $data, $expired_in): void
    {
        $this->redis()->setEx($this->prefix . ':OAuth:Code:' . $code, $expired_in, json_encode($data));
    }

    public function get($code): ?object
    {
        $code_info = json_decode($this->redis()->get($this->prefix . ':OAuth:Code:' . $code));
        $this->redis()->del($this->prefix . ':OAuth:Code:' . $code);
        return $code_info ? $code_info : null;
    }
}
