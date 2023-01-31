<?php

namespace FSA\OAuth;

class ServerClientsPDOStorage
{
    public function __construct(
        private $pdo
    ) {
    }

    public function get($client_id)
    {
        $s = ($this->pdo)()->prepare('SELECT uuid, client_id, client_secret, array_to_json(redirect_uris) AS redirect_uris FROM oauth_clients WHERE client_id=?');
        $s->execute([$client_id]);
        return $s->fetchObject();
    }
}
