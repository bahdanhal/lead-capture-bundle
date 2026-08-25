<?php

declare(strict_types=1);

namespace Bahdan\LeadCaptureBundle;

use Bahdan\LeadCaptureBundle\DependencyInjection\LeadCaptureExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class LeadCaptureBundle extends AbstractBundle implements PrependExtensionInterface
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new LeadCaptureExtension();
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('doctrine')) {
            return;
        }

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'LeadCaptureBundle' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => __DIR__ . '/Entity',
                        'prefix' => 'Bahdan\\LeadCaptureBundle\\Entity',
                        'alias' => 'LeadCaptureBundle',
                    ],
                ],
            ],
        ]);
    }
}
