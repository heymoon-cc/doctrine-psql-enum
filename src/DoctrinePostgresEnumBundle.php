<?php

namespace HeyMoon\DoctrinePostgresEnum;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use HeyMoon\DoctrinePostgresEnum\DependencyInjection\DoctrinePostgresEnumExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class DoctrinePostgresEnumBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('doctrine_postgres_enum')
                    ->children()
                        ->stringNode('type_name')->defaultValue('enum')->end()
                        ->arrayNode('migrations')->children()
                            ->booleanNode('enabled')->defaultTrue()->end()
                            ->stringNode('comment_tag')->defaultValue('DC2Enum')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DoctrinePostgresEnumExtension();
    }
}
