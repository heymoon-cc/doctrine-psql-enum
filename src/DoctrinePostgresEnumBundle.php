<?php

namespace HeyMoon\DoctrinePostgresEnum;

use HeyMoon\DoctrinePostgresEnum\DependencyInjection\DoctrinePostgresEnumExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class DoctrinePostgresEnumBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DoctrinePostgresEnumExtension();
    }
}
