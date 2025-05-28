<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Enum\AnotherExampleAttributeEnum;
use HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Enum\ExampleAttributeEnum;

#[ORM\Entity]
class HasEnumEntity
{
    #[ORM\Column]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\Column(type: 'enum', enumType: ExampleAttributeEnum::class)]
    public ExampleAttributeEnum $status;

    #[ORM\Column(type: 'enum', enumType: AnotherExampleAttributeEnum::class)]
    public AnotherExampleAttributeEnum $another;
}
