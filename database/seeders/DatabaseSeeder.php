<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogSeeder::class);

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return;
        }

        $admin = User::query()->firstOrNew(['email' => $email]);
        $admin->name = $admin->exists ? $admin->name : 'Administrador';
        $admin->password = $password;
        $admin->is_admin = true;
        $admin->email_verified_at ??= now();
        $admin->affiliate_code ??= User::newAffiliateCode();
        $admin->save();
    }
}
