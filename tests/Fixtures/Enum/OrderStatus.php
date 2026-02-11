<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Enum;

use HeyMoon\DoctrinePostgresEnum\Attribute\EnumType;

#[EnumType('order_status')]
enum OrderStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
