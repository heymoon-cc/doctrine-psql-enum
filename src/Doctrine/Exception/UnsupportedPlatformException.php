<?php

namespace HeyMoon\DoctrinePostgresEnum\Doctrine\Exception;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Exception\ORMException;
use ReflectionClass;

final class UnsupportedPlatformException extends ORMException
{
    public static function create(AbstractPlatform $platform): self
    {
        $reflection = new ReflectionClass($platform);
        return new self(
            "Platform {$reflection->getShortName()} is unsupported by heymoon/doctrine-psql-enum. Switch platform to PostgreSQL or disable this extension."
        );
    }
}
