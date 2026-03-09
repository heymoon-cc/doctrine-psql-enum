<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeyMoon\DoctrinePostgresEnum\Tests\Fixtures;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use HeyMoon\DoctrinePostgresEnum\DoctrinePostgresEnumBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * @return Bundle[]
     */
    public function registerBundles(): array
    {
        return [new FrameworkBundle(), new DoctrineBundle(), new DoctrineMigrationsBundle(), new DoctrinePostgresEnumBundle()];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('doctrine', [
            'dbal' => [
                'url' => $_ENV['DATABASE_URL'] ?? 'postgresql://postgres:mysecretpassword@some-postgres:5432/postgres?serverVersion=16&charset=utf8'
            ],
            'orm' => [
                'auto_mapping' => true,
                'default_entity_manager' => 'default',
                'mappings' => [
                    'DoctrinePostgresEnum' => [
                        'type' => 'attribute',
                        'dir' => __DIR__ . '/Entity',
                        'is_bundle' => false,
                        'prefix' => 'HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Entity',
                        'alias' => 'HeyMoonDoctrinePostgresEnum',
                    ]
                ]
            ]
        ]);

        $container->extension('doctrine_migrations', [
            'migrations_paths' => [
                'HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Migrations' => __DIR__ . '/Migrations'
            ]
        ]);

        $container->extension('doctrine_postgres_enum', [
            'type_name' => 'enum',
            'migrations' => [
                'enabled' => true,
                'comment_tag' => 'DC2Enum'
            ]
        ]);
    }

    public function shutdown(): void
    {
        parent::shutdown();
        restore_exception_handler();
    }
}
