<?php

declare(strict_types=1);

namespace Bahdan\LeadCaptureBundle\Domain;

readonly class Lead
{
    public function __construct(
        public string $email,
        public string $phone,
        public string $message,
        public string $ipHash,
        public string $source,
        public \DateTimeImmutable $createdAt,
    ) {
        if ($email === '' && $phone === '') {
            throw new \InvalidArgumentException('An email address or phone number is required.');
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Invalid email address provided for lead.');
        }
        if ($phone !== '') {
            $digitsOnly = preg_replace('/[^0-9]/', '', $phone) ?? '';
            if (
                !preg_match('#^\+?[0-9\s()./-]{7,30}$#', $phone)
                || strlen($digitsOnly) < 7
                || preg_match('/^0+$/', $digitsOnly) === 1
            ) {
                throw new \InvalidArgumentException('Invalid phone number provided for lead.');
            }
        }
        if (mb_strlen($message) > 1000) {
            throw new \InvalidArgumentException('Contact message is too long.');
        }
    }

    public static function create(string $email, string $phone, string $message, string $ipHash, string $source): static
    {
        $cleanEmail = strtolower(trim($email));
        $cleanSource = preg_replace('/[^a-z0-9_-]/i', '', $source) ?: 'website';
        $cleanMessage = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $message) ?? $message);

        return new static(
            $cleanEmail,
            trim($phone),
            $cleanMessage,
            $ipHash,
            $cleanSource,
            new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        $timestamp = isset($data['timestamp']) && (string) $data['timestamp'] !== ''
            ? (string) $data['timestamp']
            : (string) ($data['created_at'] ?? 'now');

        return new static(
            (string) ($data['email'] ?? ''),
            (string) ($data['phone'] ?? ''),
            (string) ($data['message'] ?? ''),
            (string) ($data['ip_hash'] ?? ''),
            (string) ($data['source'] ?? 'website'),
            new \DateTimeImmutable($timestamp !== '' ? $timestamp : 'now'),
        );
    }

    /** @return array{timestamp: string, email: string, phone: string, message: string, ip_hash: string, source: string} */
    public function toArray(): array
    {
        return [
            'timestamp' => $this->createdAt->format(DATE_ATOM),
            'email' => $this->email,
            'phone' => $this->phone,
            'message' => $this->message,
            'ip_hash' => $this->ipHash,
            'source' => $this->source,
        ];
    }
}
