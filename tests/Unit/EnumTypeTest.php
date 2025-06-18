<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Unit;

use Fixtures\Enum\ExampleInvalidEnum;
use Doctrine\DBAL\Exception as DBALException;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType;
use HeyMoon\DoctrinePostgresEnum\Exception\Exception;
use HeyMoon\DoctrinePostgresEnum\Tests\BaseTestCase;
use HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Enum\ExampleAttributeEnum;
use HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Enum\ExampleEnum;

final class EnumTypeTest extends BaseTestCase
{
    private const NAMES = [
        ExampleEnum::class => 'heymoon_doctrinepostgresenum_tests_fixtures_enum_exampleenum',
        ExampleAttributeEnum::class => 'example'
    ];

    /**
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType::setCommentTag
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType::getCommentTag
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType::nameFromClass
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType::parseComment
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType::comment
     * @covers \HeyMoon\DoctrinePostgresEnum\Attribute\EnumType::__construct
     * @covers \HeyMoon\DoctrinePostgresEnum\Attribute\EnumType::getName
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType::getDefaultName
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType::getReflection
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType::getSQLDeclaration
     * @throws Exception|DBALException
     */
    public function testEnumType(): void
    {
        $type = EnumType::getType(EnumType::getDefaultName());
        $platform = $this->getPlatform();
        $tag = 'TestTag';
        foreach ([ExampleEnum::class, ExampleAttributeEnum::class] as $class) {
            EnumType::setCommentTag($tag);
            $this->assertEquals($tag, EnumType::getCommentTag());
            $this->assertEquals(self::NAMES[$class], EnumType::nameFromClass($class));
            $this->assertEquals(self::NAMES[$class],
                $type->getSQLDeclaration(['enumType' => $class], $platform)
            );
            $comment = EnumType::comment($class);
            $this->assertEquals("($tag:$class)", $comment);
            $this->assertEquals($class, EnumType::parseComment($comment));
            $this->assertEquals(self::NAMES[$class],
                $type->getSQLDeclaration(['comment' => $comment], $platform)
            );
            EnumType::setCommentTag('Broken');
            $this->assertNull(EnumType::parseComment($comment));
        }
        $raw = 'raw';
        $this->assertEquals($raw, $type->getSQLDeclaration(['rawType' => $raw], $platform));
        $this->expectException(Exception::class);
        $type->getSQLDeclaration(['enumType' => ExampleInvalidEnum::class], $platform);
    }
}
