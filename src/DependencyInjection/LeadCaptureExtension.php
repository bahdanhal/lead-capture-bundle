<?php

declare(strict_types=1);

namespace Bahdan\LeadCaptureBundle\DependencyInjection;

use Bahdan\LeadCaptureBundle\Application\CaptureLead;
use Bahdan\LeadCaptureBundle\Domain\LeadRepository;
use Bahdan\LeadCaptureBundle\Infrastructure\DoctrineLeadRepository;
use Bahdan\LeadCaptureBundle\Infrastructure\JsonlLeadRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class LeadCaptureExtension extends Extension
{
    /** @param array<array-key, mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        /** @var array{secret: string, storage: string, storage_directory: string} $config */
        $config = $this->processConfiguration($configuration, $configs);

        $jsonlDefinition = new Definition(JsonlLeadRepository::class, [
            $config['storage_directory'],
        ]);
        $jsonlDefinition->setAutowired(true);
        $jsonlDefinition->setAutoconfigured(true);
        $container->setDefinition(JsonlLeadRepository::class, $jsonlDefinition);

        $targetRepository = JsonlLeadRepository::class;
        if ($config['storage'] === 'doctrine') {
            $doctrineDefinition = new Definition(DoctrineLeadRepository::class, [
                new Reference('doctrine.orm.entity_manager'),
            ]);
            $doctrineDefinition->setAutowired(true);
            $doctrineDefinition->setAutoconfigured(true);
            $container->setDefinition(DoctrineLeadRepository::class, $doctrineDefinition);
            $targetRepository = DoctrineLeadRepository::class;
        }

        $container->setAlias(LeadRepository::class, $targetRepository)->setPublic(true);

        $captureLeadDefinition = new Definition(CaptureLead::class, [
            new Reference(LeadRepository::class),
            $config['secret'],
            new Reference('event_dispatcher', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);
        $captureLeadDefinition->setAutowired(true);
        $captureLeadDefinition->setAutoconfigured(true);
        $captureLeadDefinition->setPublic(true);
        $container->setDefinition(CaptureLead::class, $captureLeadDefinition);
    }

    public function getAlias(): string
    {
        return 'lead_capture';
    }
}
