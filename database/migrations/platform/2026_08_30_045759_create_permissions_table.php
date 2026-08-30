<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('name', 150)->unique();

            $table->string('resource', 100);
            $table->string('action', 50);

            $table->text('description')->nullable();

            $table->boolean('is_system')
                ->default(false);

            $table->unsignedBigInteger('row_version')
                ->default(1);

            $table->uuid('created_by')->nullable();
            $table->timestampTz('created_at');

            $table->uuid('updated_by')->nullable();
            $table->timestampTz('updated_at');

            $table->uuid('deleted_by')->nullable();
            $table->timestampTz('deleted_at')->nullable();

            $table->index('resource');
            $table->index('action');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
