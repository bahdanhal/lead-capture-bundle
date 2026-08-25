<?php

declare(strict_types=1);

namespace Bahdan\LeadCaptureBundle\Tests;

use Bahdan\LeadCaptureBundle\Application\CaptureLead;
use Bahdan\LeadCaptureBundle\Domain\Lead;
use Bahdan\LeadCaptureBundle\Infrastructure\DoctrineLeadRepository;
use Bahdan\LeadCaptureBundle\Infrastructure\JsonlLeadRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class LeadContextTest extends TestCase
{
    public function testLeadDomainValidation(): void
    {
        $lead = Lead::create('Test@Example.Com', '', 'Please call me: budget < $5000 and > $2000.', 'fake-hash', 'seo-tool');
        self::assertSame('test@example.com', $lead->email);
        self::assertSame('Please call me: budget < $5000 and > $2000.', $lead->message);
        self::assertSame('fake-hash', $lead->ipHash);
        self::assertSame('seo-tool', $lead->source);

        $this->expectException(\InvalidArgumentException::class);
        Lead::create('not-an-email', '', '', 'fake-hash', 'seo-tool');
    }

    public function testPhoneValidationEdgeCases(): void
    {
        // Valid phones
        $lead1 = Lead::create('a@example.com', '+48 500 123 456', '', 'hash', 'web');
        self::assertSame('+48 500 123 456', $lead1->phone);

        $lead2 = Lead::create('b@example.com', '(555) 123-4567', '', 'hash', 'web');
        self::assertSame('(555) 123-4567', $lead2->phone);

        // Invalid phones: too few digits or dummy chars
        try {
            Lead::create('c@example.com', '+((((()))))----', '', 'hash', 'web');
            self::fail('Expected InvalidArgumentException for dummy phone string');
        } catch (\InvalidArgumentException) {
            // Success
        }

        try {
            Lead::create('d@example.com', '+000000000', '', 'hash', 'web');
            self::fail('Expected InvalidArgumentException for all-zero phone');
        } catch (\InvalidArgumentException) {
            // Success
        }
    }

    public function testCaptureLeadUseCaseWithJsonlRepository(): void
    {
        $directory = sys_get_temp_dir() . '/lead-test-' . bin2hex(random_bytes(4));
        $repo = new JsonlLeadRepository($directory);
        $useCase = new CaptureLead($repo, 'secret-salt');

        try {
            $lead = $useCase->execute('User@Example.com', '+48 500 000 000', 'Audit request < urgent >', '198.51.100.42', 'geo-audit');
            self::assertSame('user@example.com', $lead->email);
            self::assertSame('Audit request < urgent >', $lead->message);
            self::assertSame('geo-audit', $lead->source);

            $files = glob($directory . '/leads-*.jsonl') ?: [];
            self::assertCount(1, $files);
            $content = json_decode((string) file_get_contents($files[0]), true, flags: JSON_THROW_ON_ERROR);
            self::assertSame('user@example.com', $content['email']);
            self::assertSame('+48 500 000 000', $content['phone']);
            self::assertSame('Audit request < urgent >', $content['message']);
            self::assertSame('geo-audit', $content['source']);
            self::assertNotSame('198.51.100.42', $content['ip_hash']);

            $savedLeads = $repo->all();
            self::assertCount(1, $savedLeads);
            self::assertSame('+48 500 000 000', $savedLeads[0]->phone);

            $useCase->execute('Second@Example.com', '+48 600 111 222', 'Second request', '198.51.100.43', 'geo-audit');
            $limitedLeads = $repo->all(1);
            self::assertCount(1, $limitedLeads);
            self::assertSame('second@example.com', $limitedLeads[0]->email);
        } finally {
            foreach (glob($directory . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($directory);
        }
    }

    public function testDependencyInjectionExtension(): void
    {
        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $extension = new \Bahdan\LeadCaptureBundle\DependencyInjection\LeadCaptureExtension();
        $extension->load([
            [
                'secret' => 'test-secret',
                'storage' => 'jsonl',
                'storage_directory' => '/tmp/leads',
            ],
        ], $container);

        self::assertTrue($container->hasDefinition(\Bahdan\LeadCaptureBundle\Application\CaptureLead::class));
        self::assertTrue($container->hasAlias(\Bahdan\LeadCaptureBundle\Domain\LeadRepository::class));
        self::assertFalse($container->hasDefinition(DoctrineLeadRepository::class));
        $container->compile();
    }

    public function testDoctrineStorageWritesExplicitUtcOffset(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('insert')
            ->with('leads', self::callback(static function (array $data): bool {
                return $data['created_at'] === '2026-08-25 10:30:00+00:00';
            }));
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $lead = new Lead(
            'person@example.com',
            '',
            'Message',
            'hash',
            'test',
            new \DateTimeImmutable('2026-08-25 12:30:00+02:00'),
        );

        (new DoctrineLeadRepository($entityManager))->save($lead);
    }
}
