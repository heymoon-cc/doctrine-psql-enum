<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Enum\OrderStatus;

#[ORM\Entity]
#[ORM\Table(name: '`order`')]
class OrderEntity
{
    #[ORM\Column]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\Column(type: 'enum', enumType: OrderStatus::class)]
    public OrderStatus $status;
}
