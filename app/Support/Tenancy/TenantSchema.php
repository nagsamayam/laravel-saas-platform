<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use InvalidArgumentException;

final class TenantSchema
{
    private const POSTGRES_IDENTIFIER_MAX_LENGTH = 63;

    private const ALLOWED_PATTERN = '/^[a-z][a-z0-9_]*$/';

    public function __construct(
        private readonly string $prefix = 'tenant_',
    ) {
        if ($this->prefix === '') {
            throw new InvalidArgumentException(
                'Tenant schema prefix cannot be empty.'
            );
        }

        if (! preg_match(self::ALLOWED_PATTERN, $this->prefix)) {
            throw new InvalidArgumentException(
                'Tenant schema prefix contains invalid characters.'
            );
        }
    }

    public function fromSlug(string $slug): string
    {
        $normalized = strtolower(trim($slug));

        $normalized = preg_replace(
            '/[^a-z0-9]+/',
            '_',
            $normalized
        );

        $normalized = trim($normalized, '_');

        if ($normalized === '') {
            throw new InvalidArgumentException(
                'Tenant slug cannot produce a valid schema name.'
            );
        }

        if (! ctype_alpha($normalized[0])) {
            throw new InvalidArgumentException(
                'Tenant schema name must start with a letter.'
            );
        }

        return $this->validate(
            $this->prefix.$normalized
        );
    }

    public function validate(string $schema): string
    {
        if ($schema === '') {
            throw new InvalidArgumentException(
                'Tenant schema name cannot be empty.'
            );
        }

        if (strlen($schema) > self::POSTGRES_IDENTIFIER_MAX_LENGTH) {
            throw new InvalidArgumentException(
                'Tenant schema name cannot exceed 63 characters.'
            );
        }

        if (! preg_match(self::ALLOWED_PATTERN, $schema)) {
            throw new InvalidArgumentException(
                'Tenant schema name contains invalid characters.'
            );
        }

        return $schema;
    }

    public function quote(string $schema): string
    {
        $this->validate($schema);

        return '"'.str_replace('"', '""', $schema).'"';
    }
}
