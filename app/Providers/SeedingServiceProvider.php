<?php

namespace App\Providers;

use App\Support\Seeding\SeedingOptions;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Input\InputOption;

class SeedingServiceProvider extends ServiceProvider
{
    /**
     * @var list<class-string>
     */
    private array $seedCommands = [
        FreshCommand::class,
        RefreshCommand::class,
        SeedCommand::class,
    ];

    public function boot(): void
    {
        foreach ($this->seedCommands as $commandClass) {
            $this->app->resolving($commandClass, function ($command): void {
                $definition = $command->getDefinition();

                if (! $definition->hasOption('seed-transactional')) {
                    $definition->addOption(new InputOption(
                        'seed-transactional',
                        null,
                        InputOption::VALUE_NONE,
                        'Run transactional (demo/fixture) seeders after structural seeders',
                    ));
                }

                if (! $definition->hasOption('no-seed-transactional')) {
                    $definition->addOption(new InputOption(
                        'no-seed-transactional',
                        null,
                        InputOption::VALUE_NONE,
                        'Skip transactional (demo/fixture) seeders',
                    ));
                }
            });
        }

        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if (! in_array($event->command, ['migrate:fresh', 'migrate:refresh', 'db:seed'], true)) {
                return;
            }

            SeedingOptions::configureFromInput($event->input);
        });
    }
}
