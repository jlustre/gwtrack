<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StructuralDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = config('seeding.structural', []);

        if ($seeders === []) {
            return;
        }

        $this->call($seeders);
    }
}
