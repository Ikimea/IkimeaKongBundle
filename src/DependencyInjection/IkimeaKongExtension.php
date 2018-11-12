<?php

namespace Ikimea\Bundle\KongBundle\DependencyInjection;

use Ikimea\Bundle\KongBundle\Command\RegisterCommand;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Ikimea\Kong\Client;

class IkimeaKongExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setDefinition('ikimea.kong.guzzle_client', new Definition(\GuzzleHttp\Client::class, [
            [
                'base_uri' => $config['gateway']['url']. ':'. $config['gateway']['port'],
                'debug' => $container->getParameter('kernel.debug')
            ]
        ]));

        $definition = new Definition(Client::class, [
            new Reference('ikimea.kong.guzzle_client')
        ]);

        $definition->setPublic(true);

        $container->setDefinition('api.gateway_client', $definition);
        $container->setParameter('kong_config', $config['gateway']);

        $container
            ->register(RegisterCommand::class)
            ->addTag('console.command', ['command' => 'ikimea:gateway:register']);
    }
}
