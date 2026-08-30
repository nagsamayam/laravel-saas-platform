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
                ->default(1)
                ->after('schema_name');

            $table->uuid('created_by')
                ->nullable()
                ->after('created_at');

            $table->uuid('updated_by')
                ->nullable()
                ->after('updated_at');

            $table->uuid('deleted_by')
                ->nullable()
                ->after('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'row_version',
                'created_by',
                'updated_by',
                'deleted_by',
            ]);
        });
    }
};
