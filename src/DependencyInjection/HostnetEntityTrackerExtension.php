<?php
namespace Hostnet\Bundle\EntityTrackerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * @author Iltar van der Berg <ivanderberg@hostnet.nl>
 */
class HostnetEntityTrackerExtension extends Extension
{
    const BLAMABLE = 'Hostnet\Component\EntityBlamable\Blamable';
    const MUTATION = 'Hostnet\Component\EntityMutation\Mutation';
    const REVISION = 'Hostnet\Component\EntityRevision\Revision';

    /**
     * @see \Symfony\Component\DependencyInjection\Extension\ExtensionInterface::load()
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader        = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader->load('services.yml');

        if (array_key_exists('blamable', $config)) {
            $this->validateComponent(self::BLAMABLE, 'blamable');
            $loader->load('blamable.yml');
            $container
                ->getDefinition('entity_tracker.listener.blamable')
                ->replaceArgument(1, new Reference($config['blamable']['provider']));
        } else {
            $this->validateClass(self::BLAMABLE, 'blamable');
        }

        if (array_key_exists('revision', $config)) {
            $this->validateComponent(self::REVISION, 'revision');
            $loader->load('revision.yml');
            $container
                ->getDefinition('entity_tracker.listener.revision')
                ->replaceArgument(1, new Reference($config['revision']['factory']));
        } else {
            $this->validateClass(self::REVISION, 'revision');
        }

        if (array_key_exists('mutation', $config)) {
            $this->validateComponent(self::MUTATION, 'mutation');
            $loader->load('mutation.yml');
        } else {
            $this->validateClass(self::MUTATION, 'mutation');
        }
    }

    /**
     * @param string $annotation_class
     * @param string $config_name
     * @throws \RuntimeException
     */
    protected function validateComponent($annotation_class, $config_name)
    {
        if (class_exists($annotation_class)) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'You have configured entity_tracker.%1$s, but you did not require the package. ' .
            'To use this option, please require "hostnet/entity-%1$s-component".',
            $config_name
        ));
    }

    /**
     * @param string $annotation_class
     * @param string $config_name
     * @throws \RuntimeException
     */
    protected function validateClass($annotation_class, $config_name)
    {
        if (!class_exists($annotation_class)) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'You have required "hostnet/entity-%1$s-component" but you did not configure entity_tracker.%1$s',
            $config_name
        ));
    }
}
