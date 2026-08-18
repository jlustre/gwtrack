<?php

namespace Database\Seeders;

use App\Support\Seeding\SeedingOptions;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(StructuralDatabaseSeeder::class);

        if (SeedingOptions::shouldRunTransactional()) {
            $this->call(TransactionalDatabaseSeeder::class);
        }
    }
}
