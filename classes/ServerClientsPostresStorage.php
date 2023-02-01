<?php

namespace FSA\OAuth;

class ServerClientsPostgresStorage
{
    public function __construct(
        private $pdo
    ) {
    }

    public function get($client_id)
    {
        $s = ($this->pdo)()->prepare('SELECT id, secret, array_to_json(redirect_uris) AS redirect_uris FROM oauth_clients WHERE id=?');
        $s->execute([$client_id]);
        return $s->fetchObject();
    }
}
