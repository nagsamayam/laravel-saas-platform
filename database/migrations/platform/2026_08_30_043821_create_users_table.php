<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('name', 150);

            $table->string('email', 255)->unique();

            $table->string('password');

            $table->string('status', 30)
                ->default(UserStatus::INVITED->value)
                ->index();

            $table->unsignedBigInteger('row_version')
                ->default(1);

            $table->uuid('created_by')->nullable();
            $table->timestampTz('created_at');

            $table->uuid('updated_by')->nullable();
            $table->timestampTz('updated_at');

            $table->uuid('deleted_by')->nullable();
            $table->timestampTz('deleted_at')->nullable();

            $table->timestampTz('email_verified_at')->nullable();

            $table->rememberToken();

            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
