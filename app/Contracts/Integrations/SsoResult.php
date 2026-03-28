<?php

namespace App\Contracts\Integrations;

class SsoResult
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly array $attributes = [],
    ) {}
}
