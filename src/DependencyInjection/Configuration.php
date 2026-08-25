<?php

declare(strict_types=1);

namespace Bahdan\LeadCaptureBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('lead_capture');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('secret')
                    ->defaultValue('%kernel.secret%')
                    ->info('Secret salt used to hash IP addresses before storing.')
                ->end()
                ->enumNode('storage')
                    ->values(['doctrine', 'jsonl'])
                    ->defaultValue('doctrine')
                    ->info('Storage backend: doctrine or jsonl.')
                ->end()
                ->scalarNode('storage_directory')
                    ->defaultValue('%kernel.project_dir%/var/contact-leads')
                    ->info('Directory for JSONL lead files if jsonl storage is used.')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
