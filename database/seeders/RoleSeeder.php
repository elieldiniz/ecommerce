<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Admin', 'slug' => 'admin'],
            ['name' => 'Operação', 'slug' => 'operations'],
            ['name' => 'Financeiro', 'slug' => 'finance'],
            ['name' => 'Suporte', 'slug' => 'support'],
            ['name' => 'Cliente', 'slug' => 'customer'],
        ];

        foreach ($rows as $row) {
            Role::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
