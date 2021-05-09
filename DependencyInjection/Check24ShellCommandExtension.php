<?php

namespace Check24\ShellCommandBundle\DependencyInjection;

use Check24\ShellCommandBundle\Utils\ParameterCommand;
use Check24\ShellCommandBundle\Utils\Pipe\Pipe;
use Check24\ShellCommandBundle\Utils\Pipe\PipeFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @author    Eugen Ganshorn <eugen.ganshorn@check24.de>
 * @author    Silvester Denk <silvester.denk@check24.de>
 * @copyright 2017 CHECK24 Vergleichsportal Shopping GmbH <http://preisvergleich.check24.de>
 * @link      http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class Check24ShellCommandExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->updateContainerParameters($container, $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }

    protected function updateContainerParameters(ContainerBuilder $container, array $config)
    {
        $commandDefinitions = $this->createCommands($container, $config);
        $this->createPipes($container, $config, $commandDefinitions);
    }

    public function prepend(ContainerBuilder $container)
    {
        $config = [
            'commands' => [
                'wget_streaming' => [
                    'name' => 'wget',
                    'args' => [
                        'url' => '${url}',
                    ],
                    'options' => [
                        '--no-verbose',
                        '-O-',
                        '--dns-timeout=10',
                        '--connect-timeout=10',
                        '--read-timeout=180',
                        '--no-check-certificate',
                        '--tries=1',
                        '--waitretry=1',
                        '--user=${username}',
                        '--password=${password}',
                        '--header="accept-encoding:gzip"',
                        '--header="User-Agent: Check24/S2/COP-MOI"',
                        '--quiet',
                    ],
                ],
                'remove_empty_lines' => [
                    'name' => 'awk',
                    'args' => [
                        '"NR==1{sub(/^\xef\xbb\xbf/,\"\")}NF" RS="\r|\n|\r\n"'
                    ]
                ],
                'head' => [
                    'name' => 'head',
                    'options' => [
                        '-n 11'
                    ]
                ],
                'sponge' => [
                    'name' => 'sponge',
                ],
                'funzip' => [
                    'name' => 'funzip',
                ],
                'backup' => [
                    'name' => 'gzip',
                    'options' => [
                        '-n'
                    ],
                    'output' => [
                        'path' => '%backup_tmp_destination%/${bkpname}.csv.gz'
                    ]
                ],
                'zgrep' => [
                    'name' => 'zgrep',
                    'options' => [
                        '--binary-files=text',
                    ],
                    'args' => [
                        '""'
                    ],
                    'expectedExitCodes' => [
                        0,
                        1,
                        2
                    ]
                ],
                'tee' => [
                    'name' => 'tee',
                    'args' => [
                        '${filePath}',
                    ],
                ],
            ],
        ];

        $container->prependExtensionConfig('check24_shell_command', $config);
    }

    protected function createCommands(ContainerBuilder $container, array $config): array
    {
        $commandDefinitions = [];
        foreach ($config['commands'] as $commandName => $command) {
            $options = [];
            foreach ($command['options'] as $option) {
                if (is_scalar($option)) {
                    $options[] = $option;
                } else if (is_array($option)) {
                    $options[key($option)] = current($option);
                }
            }

            $commandDefinition = new Definition(
                ParameterCommand::class,
                [$command['name'], $command['args'] ?? [], $options ?? []]
            );

            $commandDefinition->addMethodCall('setName', [$commandName]);

            $container->setDefinition(sprintf('check24_shell_command.commands.%s', $commandName), $commandDefinition);

            $commandDefinitions[$commandName] = [
                'definition' => $commandDefinition,
                'output'     => $command['output'] ?? [],
                'exitCodes'  => $command['expectedExitCodes'] ?? [0],
            ];
        }

        return $commandDefinitions;
    }

    protected function createPipes(ContainerBuilder $container, array $config, $commandDefinitions): void
    {
        $loggerReference = new Reference('logger');

        foreach ($config['pipes'] as $pipeName => $commands) {
            $pipeParts = [];
            foreach ($commands as $id => $commandNames) {
                foreach ($commandNames as $commandName) {
                    $commandName = str_replace('-', '_', $commandName);
                    $pipeParts[$id][] = $commandDefinitions[$commandName];
                }
            }

            $pipeDefinition = new Definition(
                Pipe::class,
                [
                    $pipeParts,
                    $loggerReference,
                    $container->getDefinition('check24_shell_command.commands.tee')
                ]
            );

            $pipeDefinition->setFactory([PipeFactory::class, 'createPipe']);
            $container->setDefinition(sprintf('check24_shell_command.pipes.%s', $pipeName),$pipeDefinition);
        }
    }
}
