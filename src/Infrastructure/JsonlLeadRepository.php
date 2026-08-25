<?php

declare(strict_types=1);

namespace Bahdan\LeadCaptureBundle\Infrastructure;

use Bahdan\LeadCaptureBundle\Domain\Lead;
use Bahdan\LeadCaptureBundle\Domain\LeadRepository;

final readonly class JsonlLeadRepository implements LeadRepository
{
    public function __construct(private string $directory)
    {
    }

    public function save(Lead $lead): void
    {
        $directory = rtrim($this->directory, '/');
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to save contact request: storage directory is unavailable.');
        }

        $record = $lead->toArray();
        $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $file = $directory . '/leads-' . gmdate('Y-m') . '.jsonl';

        if (file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
            throw new \RuntimeException('Unable to save contact request.');
        }
        @chmod($file, 0660);
    }

    /** @return list<Lead> */
    public function all(?int $limit = null): array
    {
        $files = glob(rtrim($this->directory, '/') . '/leads-*.jsonl') ?: [];
        rsort($files);
        $leads = [];

        foreach ($files as $file) {
            $handle = @fopen($file, 'rb');
            if ($handle === false) {
                continue;
            }

            $fileLeads = [];
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                try {
                    $data = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                    if (is_array($data)) {
                        $fileLeads[] = Lead::fromArray($data);
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
            fclose($handle);

            for ($i = count($fileLeads) - 1; $i >= 0; --$i) {
                $leads[] = $fileLeads[$i];
                if ($limit !== null && count($leads) >= $limit) {
                    break 2;
                }
            }
        }

        usort($leads, static fn (Lead $left, Lead $right): int => $right->createdAt <=> $left->createdAt);

        return $limit !== null ? array_slice($leads, 0, $limit) : $leads;
    }
}
