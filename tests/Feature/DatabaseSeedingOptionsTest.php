<?php

namespace Tests\Feature;

use App\Support\Seeding\SeedingOptions;
use Database\Seeders\StructuralDatabaseSeeder;
use Database\Seeders\TransactionalDatabaseSeeder;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class DatabaseSeedingOptionsTest extends TestCase
{
    protected function tearDown(): void
    {
        SeedingOptions::reset();

        parent::tearDown();
    }

    public function test_transactional_seeding_is_off_by_default(): void
    {
        config(['seeding.run_transactional' => false]);
        SeedingOptions::reset();

        $this->assertFalse(SeedingOptions::shouldRunTransactional());
    }

    public function test_seed_transactional_flag_enables_transactional_seeders(): void
    {
        config(['seeding.run_transactional' => false]);
        SeedingOptions::reset();

        $input = $this->makeSeedInput(['--seed-transactional' => true]);
        SeedingOptions::configureFromInput($input);

        $this->assertTrue(SeedingOptions::shouldRunTransactional());
    }

    public function test_no_seed_transactional_flag_disables_transactional_seeders(): void
    {
        config(['seeding.run_transactional' => true]);
        SeedingOptions::reset();

        $input = $this->makeSeedInput(['--no-seed-transactional' => true]);
        SeedingOptions::configureFromInput($input);

        $this->assertFalse(SeedingOptions::shouldRunTransactional());
    }

    public function test_migrate_fresh_seed_transactional_choice_persists_for_db_seed(): void
    {
        config(['seeding.run_transactional' => false]);
        SeedingOptions::reset();

        $freshInput = $this->makeSeedInput(['--seed-transactional' => true]);
        Event::dispatch(new CommandStarting('migrate:fresh', $freshInput, new NullOutput()));

        $seedInput = $this->makeSeedInput([]);
        Event::dispatch(new CommandStarting('db:seed', $seedInput, new NullOutput()));

        $this->assertTrue(SeedingOptions::shouldRunTransactional());
    }

    public function test_seeder_manifests_are_split_in_config(): void
    {
        $structural = config('seeding.structural', []);
        $transactional = config('seeding.transactional', []);

        $this->assertContains(\Database\Seeders\RolePermissionSeeder::class, $structural);
        $this->assertContains(\Database\Seeders\CfmManagementSeeder::class, $transactional);
        $this->assertNotContains(\Database\Seeders\CfmManagementSeeder::class, $structural);
        $this->assertNotContains(StructuralDatabaseSeeder::class, $structural);
        $this->assertNotContains(TransactionalDatabaseSeeder::class, $transactional);
    }

    /**
     * @param  array<string, bool>  $options
     */
    private function makeSeedInput(array $options): ArrayInput
    {
        $definition = new InputDefinition([
            new InputOption('seed-transactional', null, InputOption::VALUE_NONE),
            new InputOption('no-seed-transactional', null, InputOption::VALUE_NONE),
        ]);

        return new ArrayInput($options, $definition);
    }
}
