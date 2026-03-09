<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Enum\ExampleAttributeEnum;

#[ORM\Entity]
#[ORM\Table(name: 'custom_schema', schema: 'custom')]
class CustomSchemaEntity
{
    #[ORM\Column]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\Column(type: 'enum', enumType: ExampleAttributeEnum::class)]
    public ExampleAttributeEnum $example;
}
