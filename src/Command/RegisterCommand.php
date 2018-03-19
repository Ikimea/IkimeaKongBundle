<?php

namespace Ikimea\Bundle\KongBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('ikimea:gateway:register')
            ->setDescription('Register route in gateway');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configs = $this->getContainer()->getParameter('kong_config');
        $client = $this->getContainer()->get('api.gateway_client');

        foreach ($configs['apis'] as $name => $config) {
            if (!$client->api('api')->has($name)) {
                $client->api('api')->create([
                    'name' => $name,
                    'hosts' => str_replace(['http://', 'https://'], '', implode(',', $config['hosts'])),
                    'uris' => $config['uris'],
                    'upstream_url' => $config['upstream_url'],
                    'preserve_host' => $config['preserve_host'],
                    'methods' => implode(',', $config['methods'])
                ]);
                $output->writeln(sprintf('  - Register Api "%s" ... ', $name));
            } else {
                $output->writeln(sprintf('"%s" Already exist ', $name));
            }
        }

        foreach ($configs['plugins'] as $plugin) {
            if (!$client->api('plugin')->has($plugin['name'])) {
                $client->api('plugin')->create([
                    'name' => $plugin['name'],
                    'config' => $plugin['config'],
                ]);
                $output->writeln(sprintf('Activate plugin "%s" config: ["%s"] ... ', $plugin['name'], var_export($plugin['config'], true)));
            }
        }

        foreach ($configs['consumers'] as $key => $config) {
            $consumer = [
                'username' => $key,
                'custom_id' => $config['custom_id'] ?? ''
            ];

            if (!$client->api('consumer')->has($key)) {
                $consumer = $client->api('consumer')->create($consumer);
                $output->writeln(sprintf('  - Register Consumer "%s" ... ', $key));
            } else {
                $consumer = $client->api('consumer')->show($key);
            }

            foreach ($config['applications'] as $name => $application) {
                $type  = 'oauth2';

                if (!$client->api('consumer')->hasApplication($consumer['id'], $type)) {
                    $applicationShow = $client->api('consumer')->createApplication($type, [
                        'name' => $name,
                        'consumer_id' => $consumer['id'],
                        'client_id' => $application['client_id'],
                        'client_secret' => $application['client_secret'],
                        'redirect_uri' => $application['redirect_uri']
                    ]);
                } else {
                    $applicationShow = $client->api('consumer')->showApplication($consumer['id'], $type);
                    $output->writeln(sprintf(' Application "%s" type "oauth2" Already exist ', $name));
                }

                foreach ($application['tokens'] as $token) {
                    $token['refresh_token'] = $token['access_token'];
                    $token['credential_id'] = $applicationShow['data'][0]['id'];

                    try {
                        $client->api('oauth2')->createToken($token);
                    } catch (\Exception $exception) {
                        $output->writeln(sprintf(' Token "%s"', var_export($token, true)));
                    }
                }
            }
        }
    }
}
