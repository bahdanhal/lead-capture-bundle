# Lead Capture Bundle

Lead ingestion and contact request processing bundle for Symfony applications with dual Doctrine ORM and JSONL storage backends.

## Features

- **Domain Model**: Strictly validated `Lead` domain value object (email/phone normalization, content size guarding, HMAC-hashed IP tracking).
- **Dual Persistence**: Production Doctrine ORM entity mapping and fallback append-only JSONL storage.
- **Application Services**: `CaptureLead` orchestration use-case.

## Installation

```bash
composer require bahdan/lead-capture-bundle
```

Doctrine is optional. Install it only when selecting the Doctrine storage backend:

```bash
composer require doctrine/orm doctrine/dbal
```

The JSONL backend does not register or resolve Doctrine services.

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
