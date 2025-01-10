<?php

namespace HeyMoon\DoctrinePostgresEnum\DependencyInjection;

use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Exception;
use LogicException;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DoctrinePostgresEnumExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $cfg = $this->processConfiguration($configuration, $configs);
        EnumType::setDefaultName($cfg['type_name'] ?? EnumType::DEFAULT_NAME);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        if ($this->isConfigEnabled($container, $cfg['migrations'])) {
            if ($this->checkMigrationsAvailability()) {
                EnumType::setCommentTag($cfg['migrations']['comment_tag']);
                $loader->load('subscriber.yaml');
            } else {
                throw new LogicException('Cannot use migrations without doctrine/doctrine-migrations-bundle.');
            }
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }

    private function checkMigrationsAvailability(): bool
    {
        return ContainerBuilder::willBeAvailable(
            'doctrine/doctrine-migrations-bundle',
            DoctrineMigrationsBundle::class,
            ['heymoon/doctrine-psql-enum']
        );
    }

    /**
     * @throws Exception
     */
    public function prepend(ContainerBuilder $container): void
    {
        $this->load($container->getExtensionConfig('doctrine_postgres_enum'), $container);
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    EnumType::getDefaultName() => EnumType::class
                ]
            ]
        ]);
    }
}
