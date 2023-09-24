<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace HeyMoon\DoctrinePostgresEnum\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use ReflectionEnum;
use ReflectionException;
use UnitEnum;

class EnumType extends Type
{
    public const DEFAULT_NAME = 'enum';

    private static string $defaultName = self::DEFAULT_NAME;
    private ?string $thisName = null;

    private static string $commentTag = 'DC2Enum';

    public static function setCommentTag(string $commentTag): void
    {
        static::$commentTag = $commentTag;
    }

    public static function getCommentTag(): string
    {
        return static::$commentTag;
    }

    public static function parseComment(string $comment): ?string
    {
        $tag = static::$commentTag;
        preg_match("/\($tag:([\w\\\]+)\)/", $comment, $matches);
        return $matches[1] ?? null;
    }

    public static function comment(string $type): string
    {
        $tag = static::$commentTag;
        return "($tag:$type)";
    }

    public static function nameFromClass(string $class): string
    {
        return
            str_replace('\\', '_', strtolower($class));
    }

    /**
     * @throws ReflectionException
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $class = null;
        if ($column['enumType'] ?? null) {
            $class = $this->getReflection($column['enumType'])->getName();
        } elseif ($column['comment'] ?? null) {
            $class = static::parseComment($column['comment']);
        }
        $method = method_exists($platform, 'getStringTypeDeclarationSQL') ? 'getStringTypeDeclarationSQL' :
            'getVarcharTypeDeclarationSQL';
        return $class ? static::nameFromClass($class) :
            ($column['rawType'] ?? $platform->$method($column));
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?string
    {
        return $value instanceof UnitEnum ?
            (property_exists($value, 'value') ? $value->value : $value->name) : $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return false;
    }

    public static function setDefaultName(string $name): void
    {
        self::$defaultName = $name;
    }

    public static function getDefaultName(): string
    {
        return self::$defaultName;
    }

    public function getName(): string
    {
        return $this->thisName ?? ($this->thisName = self::$defaultName);
    }

    /**
     * @throws ReflectionException
     */
    protected function getReflection(string $enumType): ReflectionEnum
    {
        return new ReflectionEnum($enumType);
    }
}
