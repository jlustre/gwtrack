<?php

namespace App\Exceptions\Fna;

use Exception;

class InvalidFnaStatusTransitionException extends Exception
{
    public static function fromTo(string $from, string $to): self
    {
        return new self("Invalid FNA status transition from [{$from}] to [{$to}].");
    }
}
