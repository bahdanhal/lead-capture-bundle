<?php

declare(strict_types=1);

namespace Bahdan\LeadCaptureBundle;

use Bahdan\LeadCaptureBundle\DependencyInjection\LeadCaptureExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class LeadCaptureBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new LeadCaptureExtension();
    }
}
