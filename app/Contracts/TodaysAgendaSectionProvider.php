<?php

namespace App\Contracts;

use App\Models\User;
use Carbon\CarbonInterface;

interface TodaysAgendaSectionProvider
{
    public function key(): string;

    public function label(): string;

    public function icon(): string;

    public function sort(): int;

    public function isEnabledFor(User $user): bool;

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     icon: string,
     *     count: int,
     *     items: list<array{
     *         id: string,
     *         title: string,
     *         subtitle: string|null,
     *         meta: string|null,
     *         url: string|null,
     *         badge: string|null,
     *         highlight: bool,
     *         sort_at: string|null
     *     }>,
     *     empty_message: string,
     *     action: array{label: string, url: string}|null
     * }
     */
    public function sectionFor(User $user, CarbonInterface $dayStart, CarbonInterface $dayEnd): array;
}
