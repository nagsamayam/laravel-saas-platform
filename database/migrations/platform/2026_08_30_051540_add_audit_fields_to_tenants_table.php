<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->unsignedBigInteger('row_version')
                ->default(1);

            $table->uuid('created_by')
                ->nullable();

            $table->uuid('updated_by')
                ->nullable();

            $table->uuid('deleted_by')
                ->nullable();

            $table->timestampTz('deleted_at')
                ->nullable();

            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropIndex('tenants_deleted_at_index');

            $table->dropColumn([
                'row_version',
                'created_by',
                'updated_by',
                'deleted_by',
                'deleted_at',
            ]);
        });
    }
};
