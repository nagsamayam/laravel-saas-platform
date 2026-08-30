<?php

declare(strict_types=1);

namespace App\Support\Audit;

interface Auditable
{
    public function getAuditActorId(): ?string;

    public function getAuditResourceType(): string;

    public function getAuditResourceId(): string;
}
