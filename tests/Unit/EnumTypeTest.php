<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Unit;

use Doctrine\DBAL\Exception;
use Fixtures\Enum\ExampleInvalidEnum;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType;
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
     * @covers EnumType::setCommentTag
     * @covers EnumType::getCommentTag
     * @covers EnumType::nameFromClass
     * @covers EnumType::parseComment
     * @covers EnumType::comment
     * @throws Exception
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
