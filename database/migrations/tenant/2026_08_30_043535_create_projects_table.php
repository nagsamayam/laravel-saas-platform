<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('status', 30)->index();

            $table->unsignedBigInteger('row_version')->default(1);

            $table->uuid('created_by')->nullable();
            $table->timestampTz('created_at');

            $table->uuid('updated_by')->nullable();
            $table->timestampTz('updated_at');

            $table->uuid('deleted_by')->nullable();
            $table->timestampTz('deleted_at')->nullable();

            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
