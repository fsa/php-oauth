<?php

namespace FSA\OAuth;

interface TokenStorageInterface
{

    public function set(string $token, object|array $data, int $expired_in): void;
    public function get(string $token): ?object;
}
