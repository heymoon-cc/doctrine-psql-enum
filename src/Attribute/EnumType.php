<?php

namespace HeyMoon\DoctrinePostgresEnum\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class EnumType
{
    public function __construct(
        private string $name
    ) {}

    public function getName(): string
    {
        return $this->name;
    }
}
