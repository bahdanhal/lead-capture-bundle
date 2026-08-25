<?php

declare(strict_types=1);

namespace Bahdan\LeadCaptureBundle\Infrastructure;

use Bahdan\LeadCaptureBundle\Domain\Lead;
use Bahdan\LeadCaptureBundle\Domain\LeadRepository;
use Bahdan\LeadCaptureBundle\Entity\LeadEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineLeadRepository implements LeadRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Lead $lead): void
    {
        $this->entityManager->getConnection()->insert('leads', [
            'email' => $lead->email,
            'phone' => $lead->phone,
            'message' => $lead->message,
            'ip_hash' => $lead->ipHash,
            'source' => $lead->source,
            'created_at' => $lead->createdAt->setTimezone(new \DateTimeZone('UTC')),
        ], [
            'created_at' => Types::DATETIMETZ_IMMUTABLE,
        ]);
    }

    /** @return list<Lead> */
    public function all(?int $limit = null): array
    {
        $repository = $this->entityManager->getRepository(LeadEntity::class);
        /** @var list<LeadEntity> $entities */
        $entities = $repository->findBy([], ['createdAt' => 'DESC'], $limit);

        return array_map(
            static fn (LeadEntity $entity): Lead => new Lead(
                $entity->getEmail(),
                $entity->getPhone(),
                $entity->getMessage(),
                $entity->getIpHash(),
                $entity->getSource(),
                $entity->getCreatedAt(),
            ),
            $entities
        );
    }
}
