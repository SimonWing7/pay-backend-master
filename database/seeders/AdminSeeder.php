<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hardcoded credentials below — safe for local dev only. Refuse to
        // run anywhere else so this can't accidentally create a predictable
        // admin login on staging/production if someone runs db:seed there.
        if (!app()->environment('local', 'testing')) {
            $this->command?->warn('AdminSeeder skipped — only runs in local/testing environments.');
            return;
        }

        Admin::firstOrCreate(
            ['email' => 'admin@edfundo.com'],
            [
                'name' => 'EdFundo Admin',
                'email' => 'admin@edfundo.com',
                'password' => Hash::make('EdFundo@2026'),
            ]
        );
    }
}
