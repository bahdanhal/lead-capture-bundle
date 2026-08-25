# Lead Capture Bundle

Lead ingestion and contact request processing bundle for Symfony applications with dual Doctrine ORM and JSONL storage backends.

[Packagist](https://packagist.org/packages/bahdan/lead-capture-bundle) · [GitHub](https://github.com/bahdanhal/lead-capture-bundle)

## Features

- **Domain Model**: Strictly validated `Lead` domain value object (email/phone normalization, content size guarding, HMAC-hashed IP tracking).
- **Dual Persistence**: Production Doctrine ORM entity mapping and fallback append-only JSONL storage.
- **Application Services**: `CaptureLead` orchestration use-case.
- **Integration Hook**: Dispatches `LeadCaptured` after persistence so applications can notify email, Slack, Telegram, a CRM, or a webhook without coupling those transports to the bundle.
- **Portable Doctrine Storage**: Attribute mappings are registered automatically and date values use DBAL types across PostgreSQL, MySQL/MariaDB, and SQLite.
- **Bounded JSONL Reads**: Limited admin queries read newest records from disk without loading an entire monthly partition.

## Installation

```bash
composer require bahdan/lead-capture-bundle
```

Doctrine is optional. Install it only when selecting the Doctrine storage backend:

```bash
composer require doctrine/orm doctrine/dbal
```

The JSONL backend does not register or resolve Doctrine services.

When Doctrine storage is selected, the bundle registers its entity mapping automatically. Your application still owns schema migrations; generate and review a migration after enabling the bundle.

## Notifications and webhooks

Listen for `Bahdan\LeadCaptureBundle\Event\LeadCaptured` with a regular Symfony event listener. The event contains the validated `Lead`; the listener can hand it to Messenger for asynchronous email, chat, CRM, or webhook delivery.

```php
use Bahdan\LeadCaptureBundle\Event\LeadCaptured;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class NotifyAboutLead
{
    public function __invoke(LeadCaptured $event): void
    {
        // Dispatch an application-specific notification or Messenger message.
    }
}
```

## Configuration

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Bahdan\LeadCaptureBundle\LeadCaptureBundle::class => ['all' => true],
];
```

## License

MIT
