<?php

namespace FSA\OAuth;

interface AccessTokenStorageInterface
{

    public function set(string $token, object|array $data, $expired_in): void;
    public function get($token): ?object;
}
