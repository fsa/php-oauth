<?php

namespace FSA\OAuth;

use PDO;

class ServerClientsPostgresStorage extends AbstractPostgres implements ServerClientsStorageInterface
{

    public function get($client_id): ?object
    {
        $s = $this->pdo()->prepare('SELECT id, secret, array_to_json(redirect_uris) AS redirect_uris FROM oauth_clients WHERE id=?');
        $s->execute([$client_id]);
        return $s->fetchObject() ?: null;
    }
}
