<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType;
use HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Enum\ExampleEnum;
use HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Platform\TestPostgreSQLPlatform;
use PHPUnit\Framework\TestCase;
use UnitEnum;

abstract class BaseTestCase extends TestCase
{
    private ?TestPostgreSQLPlatform $platform = null;

    /**
     * @throws Exception
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        Type::overrideType(EnumType::getDefaultName(), EnumType::class);
        parent::__construct($name, $data, $dataName);
    }

    public function getPlatform(): AbstractPlatform
    {
        return $this->platform ?? ($this->platform = new TestPostgreSQLPlatform());
    }

    /**
     * @throws Exception
     */
    public function getColumn(string $name, UnitEnum $enum = ExampleEnum::Test): Column
    {
        return new Column($name, EnumType::getType(EnumType::getDefaultName()), [
            'customSchemaOptions' => [
                'enumType' => $enum::class
            ],
            'comment' => EnumType::comment($enum::class)
        ]);
    }
}
