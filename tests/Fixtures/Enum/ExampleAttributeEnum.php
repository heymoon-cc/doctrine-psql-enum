<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Enum;

use HeyMoon\DoctrinePostgresEnum\Attribute\EnumType;

#[EnumType('example')]
enum ExampleAttributeEnum: string
{
    case Test = 'test';
    case Case = 'case';
}
