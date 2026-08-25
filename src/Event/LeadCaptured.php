<?php

declare(strict_types=1);

namespace Bahdan\LeadCaptureBundle\Event;

use Bahdan\LeadCaptureBundle\Domain\Lead;

final readonly class LeadCaptured
{
    public function __construct(public Lead $lead)
    {
    }
}
