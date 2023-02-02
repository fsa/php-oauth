<?php

namespace FSA\OAuth;

class RefreshTokenPostgresStorage implements TokenStorageInterface
{

    public function __construct(
        private callable $pdo,
        private string $name
    ) {
    }

    public function set($token, $data, int $expired_in): void
    {
        $client_id = $data['client_id'];
        unset($data['client_id']);
        $pdo = ($this->pdo)();
        $stmt = $pdo->prepare('INSERT INTO oauth_refresh_tokens (token, expires_at, client_id, payload) VALUES (?, ?, ?, ?)');
        $stmt->execute([$token, date('c', time() + $expired_in, $client_id), json_encode($data, JSON_UNESCAPED_UNICODE)]);
    }

    public function get($token): ?object
    {
        $pdo = ($this->pdo)();
        $stmt = $pdo->prepare('SELECT * FROM oauth_refresh_tokens WHERE token=? AND expires_at<CURRENT_TIMESTAMP');
        $stmt->execute([$token]);
        $result = $stmt->fetchObject();
        if (!$result) {
            return null;
        }
        $token_info = $result->payload;
        $token_info->client_id = $result->client_id;
        return $token_info;
    }
}
