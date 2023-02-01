<?php

namespace FSA\OAuth;

class HtmlException extends \Exception
{
    public function __construct($code, $message, private ?string $description = null)
    {
        parent::__construct($message, $code);
    }

    public function getDescription() {
        return $this->description;
    }
}
