<?php

namespace FSA\OAuth;

interface ServerClientsStorageInterface
{

    public function get($client_id): ?object;
}
