<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_user_roles', function (Blueprint $table): void {
            $table->uuid('tenant_user_id');
            $table->uuid('role_id');

            $table->primary([
                'tenant_user_id',
                'role_id',
            ]);

            $table->foreign('tenant_user_id')
                ->references('id')
                ->on('tenant_users')
                ->cascadeOnDelete();

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user_roles');
    }
};
