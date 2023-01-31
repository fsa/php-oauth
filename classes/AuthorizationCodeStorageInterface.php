<?php

namespace FSA\OAuth;

interface AuthorizationCodeStorageInterface
{

    public function set(string $code, object|array $data, int $expired_in): void;
    public function get(string $code): ?object;
}
