<?php

declare(strict_types=1);

namespace Bahdan\LeadCaptureBundle\Domain;

interface LeadRepository
{
    public function save(Lead $lead): void;

    /** @return list<Lead> */
    public function all(): array;
}
