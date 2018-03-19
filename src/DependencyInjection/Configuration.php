<?php

namespace Ikimea\Bundle\KongBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ikimea_kong');

        $rootNode
            ->children()
                ->scalarNode('service_name')->isRequired()->end()
                ->booleanNode('activate_mock')->defaultTrue()->end()
                ->scalarNode('user_entity')->defaultNull()->end()
            ->end()
        ;

        $this->addGatewayConfiguration($rootNode);

        return $treeBuilder;
    }

    public function addGatewayConfiguration(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('gateway')
                    ->children()
                        ->scalarNode('url')->isRequired()->end()
                        ->scalarNode('port')->isRequired()->end()
                        ->arrayNode('apis')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->arrayNode('hosts')->prototype('scalar')->end()->end()
                                    ->scalarNode('upstream_url')->end()
                                    ->scalarNode('uris')->isRequired()->end()
                                    ->booleanNode('preserve_host')->defaultFalse()->end()
                                    ->scalarNode('methods')->defaultValue(['GET', 'POST', 'PUT', 'DELETE'])->end()
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('consumers')
                            ->useAttributeAsKey('username')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('custom_id')->defaultValue(null)->end()
                                    ->arrayNode('applications')
                                        ->defaultValue([])
                                        ->useAttributeAsKey('name')
                                            ->prototype('array')
                                            ->children()
                                                ->scalarNode('client_id')->isRequired()->end()
                                                ->scalarNode('client_secret')->isRequired()->end()
                                                ->arrayNode('redirect_uri')
                                                    ->prototype('scalar')->end()
                                                ->end()
                                                ->arrayNode('tokens')
                                                    ->prototype('array')
                                                        ->children()
                                                            ->scalarNode('token_type')->isRequired()->defaultValue('bearer')->end()
                                                            ->scalarNode('access_token')->isRequired()->end()
                                                            ->scalarNode('refresh_token')->defaultValue(null)->end()
                                                            ->integerNode('expires_in')->isRequired()->defaultValue(7200)->end()
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('plugins')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->arrayNode('config')
                                        ->useAttributeAsKey('name')
                                        ->prototype('scalar')->end()
                                    ->end()
                                    ->scalarNode('api_name')->defaultValue(null)->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
