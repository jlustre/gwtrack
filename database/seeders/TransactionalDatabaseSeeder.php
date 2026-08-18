<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TransactionalDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = config('seeding.transactional', []);

        if ($seeders === []) {
            return;
        }

        $this->call($seeders);
    }
}
