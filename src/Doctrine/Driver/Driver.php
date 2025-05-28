<?php

namespace HeyMoon\DoctrinePostgresEnum\Doctrine\Driver;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\ServerVersionProvider;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Platform\DoctrineEnumColumnPlatform;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProviderInterface;

final class Driver extends AbstractDriverMiddleware
{
    public function __construct(DriverInterface $driver, private readonly MetaDataProviderInterface $metaDataProvider)
    {
        parent::__construct($driver);
    }

    public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
    {
        $platform = parent::getDatabasePlatform($versionProvider);

        // we don't support any other platform
        if (!$platform instanceof PostgreSQLPlatform) {
            return $platform;
        }

        return new DoctrineEnumColumnPlatform($platform, $this->metaDataProvider);
    }
}
