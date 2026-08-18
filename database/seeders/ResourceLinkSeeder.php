<?php

namespace Database\Seeders;

use App\Services\DocumentLinkSyncService;
use Illuminate\Database\Seeder;

class ResourceLinkSeeder extends Seeder
{
    public function run(): void
    {
        app(DocumentLinkSyncService::class)->syncAll();
    }
}
