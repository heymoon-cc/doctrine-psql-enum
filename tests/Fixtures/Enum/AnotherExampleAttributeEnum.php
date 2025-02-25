<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Enum;

use HeyMoon\DoctrinePostgresEnum\Attribute\EnumType;

#[EnumType('another_example')]
enum AnotherExampleAttributeEnum: string
{
    case Foo = 'foo';
    case Bar = 'bar';
}
