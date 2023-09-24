<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Unit;

use Doctrine\DBAL\Exception;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType;
use HeyMoon\DoctrinePostgresEnum\Tests\BaseTestCase;
use HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Enum\ExampleEnum;

final class EnumTypeTest extends BaseTestCase
{
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
        $tag = 'TestTag';
        EnumType::setCommentTag($tag);
        $this->assertEquals($tag, EnumType::getCommentTag());
        $class = ExampleEnum::class;
        $type = EnumType::getType(EnumType::getDefaultName());
        $platform = $this->getPlatform();
        $this->assertEquals(EnumType::nameFromClass($class),
            $type->getSQLDeclaration(['enumType' => $class], $platform)
        );
        $comment = EnumType::comment($class);
        $this->assertEquals("($tag:$class)", $comment);
        $this->assertEquals($class, EnumType::parseComment($comment));
        EnumType::setCommentTag('Broken');
        $this->assertNull(EnumType::parseComment($comment));
        $raw = 'raw';
        $this->assertEquals($raw, $type->getSQLDeclaration(['rawType' => $raw], $platform));
    }
}
