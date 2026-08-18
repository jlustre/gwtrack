<?php

namespace App\Support\Seeding;

use Symfony\Component\Console\Input\InputInterface;

class SeedingOptions
{
    private static ?bool $runTransactional = null;

    public static function configureFromInput(InputInterface $input): void
    {
        if ($input->hasParameterOption(['--seed-transactional'], true)) {
            self::$runTransactional = true;

            return;
        }

        if ($input->hasParameterOption(['--no-seed-transactional'], true)) {
            self::$runTransactional = false;

            return;
        }

        if (self::$runTransactional !== null) {
            return;
        }

        self::$runTransactional = (bool) config('seeding.run_transactional', false);
    }

    public static function shouldRunTransactional(): bool
    {
        if (self::$runTransactional !== null) {
            return self::$runTransactional;
        }

        return (bool) config('seeding.run_transactional', false);
    }

    public static function forceTransactional(bool $runTransactional): void
    {
        self::$runTransactional = $runTransactional;
    }

    public static function reset(): void
    {
        self::$runTransactional = null;
    }
}
