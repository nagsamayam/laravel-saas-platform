<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Platform\Role;
use App\Models\Platform\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env(
            'PLATFORM_ADMIN_EMAIL',
            'admin@example.com'
        );

        $password = (string) env(
            'PLATFORM_ADMIN_PASSWORD',
            'change-me-in-local-environment'
        );

        $user = User::query()->firstOrCreate(
            [
                'email' => $email,
            ],
            [
                'name' => 'Platform Administrator',
                'password' => Hash::make($password),
                'status' => UserStatus::ACTIVE,
            ],
        );

        if ($user->status !== UserStatus::ACTIVE) {
            $user->update([
                'status' => UserStatus::ACTIVE,
            ]);
        }

        $role = Role::query()
            ->where('slug', 'platform_admin')
            ->where('type', 'platform')
            ->firstOrFail();

        $user->platformRoles()->syncWithoutDetaching([
            $role->id,
        ]);
    }
}
