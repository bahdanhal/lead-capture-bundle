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
        if ($limit !== null && $limit <= 0) {
            return [];
        }

        $files = glob(rtrim($this->directory, '/') . '/leads-*.jsonl') ?: [];
        rsort($files);
        $leads = [];

        foreach ($files as $file) {
            if ($limit !== null) {
                foreach ($this->reverseLines($file) as $line) {
                    $lead = $this->deserialize($line);
                    if ($lead === null) {
                        continue;
                    }
                    $leads[] = $lead;
                    if (count($leads) >= $limit) {
                        break 2;
                    }
                }
                continue;
            }

            $handle = @fopen($file, 'rb');
            if ($handle === false) {
                continue;
            }

            while (($line = fgets($handle)) !== false) {
                $lead = $this->deserialize($line);
                if ($lead !== null) {
                    $leads[] = $lead;
                }
            }
            fclose($handle);
        }

        usort($leads, static fn (Lead $left, Lead $right): int => $right->createdAt <=> $left->createdAt);

        return $leads;
    }

    private function deserialize(string $line): ?Lead
    {
        $line = trim($line);
        if ($line === '') {
            return null;
        }

        try {
            $data = json_decode($line, true, flags: JSON_THROW_ON_ERROR);

            return is_array($data) ? Lead::fromArray($data) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return \Generator<int, string> */
    private function reverseLines(string $file): \Generator
    {
        $handle = @fopen($file, 'rb');
        if ($handle === false) {
            return;
        }

        try {
            fseek($handle, 0, SEEK_END);
            $position = ftell($handle);
            if ($position === false) {
                return;
            }
            $buffer = '';

            while ($position > 0) {
                $readSize = min(8192, $position);
                $position -= $readSize;
                fseek($handle, $position);
                $chunk = fread($handle, $readSize);
                if ($chunk === false) {
                    return;
                }
                $buffer = $chunk . $buffer;

                while (($newline = strrpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, $newline + 1);
                    $buffer = substr($buffer, 0, $newline);
                    if ($line !== '') {
                        yield $line;
                    }
                }
            }

            if ($buffer !== '') {
                yield $buffer;
            }
        } finally {
            fclose($handle);
        }
    }
}
