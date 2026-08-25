<?php

declare(strict_types=1);

namespace Bahdan\LeadCaptureBundle\Application;

use Bahdan\LeadCaptureBundle\Domain\Lead;
use Bahdan\LeadCaptureBundle\Domain\LeadRepository;
use Bahdan\LeadCaptureBundle\Event\LeadCaptured;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class CaptureLead
{
    public function __construct(
        private LeadRepository $repository,
        private string $secret,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {
    }

    public function execute(string $email, string $phone, string $message, string $ipAddress, string $source): Lead
    {
        $ipHash = substr(hash_hmac('sha256', $ipAddress, $this->secret), 0, 20);
        $lead = Lead::create($email, $phone, $message, $ipHash, $source);
        $this->repository->save($lead);
        $this->eventDispatcher?->dispatch(new LeadCaptured($lead));

        return $lead;
    }
}
