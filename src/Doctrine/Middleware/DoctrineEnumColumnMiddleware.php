<?php

namespace HeyMoon\DoctrinePostgresEnum\Doctrine\Middleware;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsMiddleware;
use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Driver\Driver;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProviderInterface;

#[AsMiddleware]
final class DoctrineEnumColumnMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly MetaDataProviderInterface $metaDataProvider)
    {
    }

    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new Driver($driver, $this->metaDataProvider);
    }
}
