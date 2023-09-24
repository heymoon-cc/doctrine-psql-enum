<?php

namespace HeyMoon\DoctrinePostgresEnum\DependencyInjection;

use HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('doctrine_postgres_enum');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('type_name')->defaultValue(EnumType::DEFAULT_NAME)->end()
                ->arrayNode('migrations')
                ->children()
                    ->scalarNode('enabled')->defaultTrue()->end()
                    ->scalarNode('comment_tag')->defaultValue('DC2Enum')->end()
                ->end()
            ->end();
        return $treeBuilder;
    }
}