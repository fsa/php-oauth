<?php

namespace FSA\OAuth;

use PDO;

abstract class AbstractPostgres
{
    private PDO $pdo;
    private $pdo_callback;

    public function __construct(PDO|callable $pdo)
    {
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
        } else {
            $this->pdo_callback = $pdo;
        }
    }

    protected function pdo()
    {
        if (is_null($this->pdo)) {
            $this->pdo = ($this->pdo_callback)();
        }
        return $this->pdo;
    }
}
