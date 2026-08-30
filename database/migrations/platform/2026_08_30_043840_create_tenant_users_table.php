<?php

declare(strict_types=1);

use App\Enums\TenantMembershipStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('user_id');

            $table->string('status', 30)
                ->default(TenantMembershipStatus::INVITED->value)
                ->index();

            $table->unsignedBigInteger('row_version')
                ->default(1);

            $table->uuid('created_by')->nullable();
            $table->timestampTz('created_at');

            $table->uuid('updated_by')->nullable();
            $table->timestampTz('updated_at');

            $table->uuid('deleted_by')->nullable();
            $table->timestampTz('deleted_at')->nullable();

            $table->unique(['tenant_id', 'user_id']);

            $table->index([
                'tenant_id',
                'status',
            ]);

            $table->index('deleted_at');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_users');
    }
};
