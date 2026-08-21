<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Rank;
use App\Models\Team;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Support\LocationOptions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a sponsorship genealogy for tree / hierarchy testing (realistic names, stable emails).
 *
 * Login: downline-owner@gwtrack.com / Password123
 *
 * Minimum depth from root: 4 levels (root → leader → … → member).
 * Branches: wide (Avery), deep chain (Bianca), mixed legs (Caleb), captains + leaves (Dana).
 *
 * Stable test emails:
 * - genealogy.leaf.dana.01@gwtrack.com (leaf, no downline)
 * - genealogy.deep.bianca.l8@gwtrack.com (deepest chain node)
 * - genealogy.wide.avery.01@gwtrack.com (wide branch entry)
 */
class DownlineManagementSeeder extends Seeder
{
    private const FAKER_SEED = 4242;

    private const MIN_SPONSORSHIP_DEPTH = 4;

    /** @var list<string> */
    private const FIRST_NAMES = [
        'Elliot', 'Farah', 'Gabriel', 'Harper', 'Iris', 'Jonas', 'Kara', 'Luis', 'Mina', 'Noah',
        'Olivia', 'Priya', 'Quinn', 'Rafael', 'Sofia', 'Theo', 'Uma', 'Victor', 'Wendy', 'Xavier',
        'Yara', 'Zane', 'Amelia', 'Brandon', 'Celeste', 'Dominic', 'Elena', 'Felix', 'Gia', 'Hugo',
        'Isla', 'Jasper', 'Keira', 'Leo', 'Maya', 'Nolan', 'Opal', 'Parker', 'Ruby', 'Silas',
    ];

    /** @var list<string> */
    private const LAST_NAMES = [
        'Carter', 'Singh', 'Cruz', 'Lee', 'Patel', 'Wright', 'Brooks', 'Rivera', 'Santos', 'Evans',
        'Grant', 'Thomas', 'Walker', 'Young', 'Kim', 'Bennett', 'Flores', 'Hall', 'Scott', 'Price',
        'Nelson', 'Cooper', 'Foster', 'King', 'Ross', 'Gray', 'Ward', 'Hughes', 'Coleman', 'Bell',
        'Hayes', 'Morales', 'Reed', 'Bailey', 'Howard', 'Torres', 'Nguyen', 'Murphy', 'Cook', 'Rogers',
    ];

    private int $randomSeed = self::FAKER_SEED;

    private Team $team;

    private User $root;

    private User $defaultMentor;

    /** @var array<string, int> */
    private array $rankIds = [];

    private int $memberSequence = 0;

    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);

        $this->randomSeed = self::FAKER_SEED;

        $this->team = Team::firstOrCreate(
            ['name' => 'Wealth Legacy Alliance'],
            ['description' => 'Default downline team for GWTrack demo hierarchy.', 'is_active' => true]
        );

        $this->rankIds = Rank::query()->pluck('id', 'code')->all();

        $this->root = $this->seedUser(
            email: 'downline-owner@gwtrack.com',
            name: 'Morgan Executive',
            rankCode: 'SVP',
            role: 'agency-owner',
            sponsor: null,
            joinedMonthsAgo: 36,
        );

        $leaders = [
            $this->seedUser('avery.stone@gwtrack.com', 'Avery Stone', 'SA', 'team-leader', $this->root, 28),
            $this->seedUser('bianca.reyes@gwtrack.com', 'Bianca Reyes', 'ASC', 'certified-field-mentor', $this->root, 26),
            $this->seedUser('caleb.morgan@gwtrack.com', 'Caleb Morgan', 'SA', 'team-leader', $this->root, 24),
            $this->seedUser('dana.chen@gwtrack.com', 'Dana Chen', 'ASC', 'trainer', $this->root, 22),
        ];

        $this->defaultMentor = $leaders[1];

        $this->seedWideBranch($leaders[0], prefix: 'avery');
        $this->seedDeepChain($leaders[1], prefix: 'bianca', levels: 8);
        $this->seedMixedBranch($leaders[2], prefix: 'caleb');
        $this->seedCaptainBranch($leaders[3], prefix: 'dana');

        $this->seedProfiles();

        app(DownlineHierarchyService::class)->rebuild();
    }

    private function seedWideBranch(User $leader, string $prefix): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $email = $i === 1
                ? "genealogy.wide.{$prefix}.01@gwtrack.com"
                : $this->nextEmail("wide.{$prefix}");

            $direct = $this->seedUser(
                email: $email,
                name: $this->randomName(),
                rankCode: $i % 3 === 0 ? 'ASC' : 'TA',
                role: 'member',
                sponsor: $leader,
                joinedMonthsAgo: 20 - ($i % 12),
                mentor: $this->defaultMentor,
            );

            if ($i <= 9) {
                $this->seedSubtree(
                    sponsor: $direct,
                    emailPrefix: "wide.{$prefix}.{$i}",
                    levelsBelow: self::MIN_SPONSORSHIP_DEPTH - 2,
                    minChildren: 2,
                    maxChildren: 4,
                );
            }
        }
    }

    private function seedDeepChain(User $leader, string $prefix, int $levels): void
    {
        $sponsor = $leader;

        for ($level = 1; $level <= $levels; $level++) {
            $email = match (true) {
                $level === 1 => "genealogy.deep.{$prefix}.l1@gwtrack.com",
                $level === $levels => "genealogy.deep.{$prefix}.l{$level}@gwtrack.com",
                default => $this->nextEmail("deep.{$prefix}.mid"),
            };

            $sponsor = $this->seedUser(
                email: $email,
                name: $this->randomName(),
                rankCode: $level <= 2 ? 'ASC' : 'TA',
                role: 'member',
                sponsor: $sponsor,
                joinedMonthsAgo: 22 - $level,
                mentor: $this->defaultMentor,
            );
        }
    }

    private function seedMixedBranch(User $leader, string $prefix): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->seedUser(
                email: $this->nextEmail("mixed.{$prefix}.leaf"),
                name: $this->randomName(),
                rankCode: 'TA',
                role: 'member',
                sponsor: $leader,
                joinedMonthsAgo: 16 - ($i % 10),
                mentor: $this->defaultMentor,
                isActive: $i % 6 !== 0,
            );
        }

        for ($leg = 1; $leg <= 3; $leg++) {
            $legRoot = $this->seedUser(
                email: $leg === 1
                    ? "genealogy.mixed.{$prefix}.leg-root@gwtrack.com"
                    : $this->nextEmail("mixed.{$prefix}.leg"),
                name: $this->randomName(),
                rankCode: 'TA',
                role: 'member',
                sponsor: $leader,
                joinedMonthsAgo: 14,
                mentor: $this->defaultMentor,
            );

            $this->seedSubtree(
                sponsor: $legRoot,
                emailPrefix: "mixed.{$prefix}.leg{$leg}",
                levelsBelow: self::MIN_SPONSORSHIP_DEPTH - 1,
                minChildren: 1,
                maxChildren: 3,
            );
        }
    }

    private function seedCaptainBranch(User $leader, string $prefix): void
    {
        for ($captain = 1; $captain <= 5; $captain++) {
            $captainUser = $this->seedUser(
                email: $this->nextEmail("captain.{$prefix}"),
                name: $this->randomName(),
                rankCode: $captain % 2 === 0 ? 'ASC' : 'TA',
                role: 'member',
                sponsor: $leader,
                joinedMonthsAgo: 15 - $captain,
                mentor: $this->defaultMentor,
            );

            for ($leaf = 1; $leaf <= 6; $leaf++) {
                $email = ($captain === 1 && $leaf === 1)
                    ? "genealogy.leaf.{$prefix}.01@gwtrack.com"
                    : $this->nextEmail("leaf.{$prefix}");

                $this->seedUser(
                    email: $email,
                    name: $this->randomName(),
                    rankCode: 'TA',
                    role: 'member',
                    sponsor: $captainUser,
                    joinedMonthsAgo: 12 - ($leaf % 8),
                    mentor: $this->defaultMentor,
                    isActive: ($captain + $leaf) % 7 !== 0,
                );
            }
        }
    }

    /**
     * @return list<User>
     */
    private function seedSubtree(
        User $sponsor,
        string $emailPrefix,
        int $levelsBelow,
        int $minChildren,
        int $maxChildren,
    ): array {
        if ($levelsBelow < 1) {
            return [];
        }

        $created = [];
        $childCount = $this->randomInt($minChildren, $maxChildren);

        for ($index = 1; $index <= $childCount; $index++) {
            $child = $this->seedUser(
                email: $this->nextEmail($emailPrefix),
                name: $this->randomName(),
                rankCode: $index % 4 === 0 ? 'ASC' : 'TA',
                role: 'member',
                sponsor: $sponsor,
                joinedMonthsAgo: $this->randomInt(3, 18),
                mentor: $this->defaultMentor,
            );

            $created[] = $child;

            if ($levelsBelow > 1) {
                $this->seedSubtree(
                    sponsor: $child,
                    emailPrefix: "{$emailPrefix}.{$index}",
                    levelsBelow: $levelsBelow - 1,
                    minChildren: $minChildren,
                    maxChildren: $maxChildren,
                );
            }
        }

        return $created;
    }

    private function randomInt(int $min, int $max): int
    {
        if ($min >= $max) {
            return $min;
        }

        $this->randomSeed = (1103515245 * $this->randomSeed + 12345) & 0x7fffffff;

        return $min + ($this->randomSeed % ($max - $min + 1));
    }

    private function randomName(): string
    {
        $first = self::FIRST_NAMES[$this->randomInt(0, count(self::FIRST_NAMES) - 1)];
        $last = self::LAST_NAMES[$this->randomInt(0, count(self::LAST_NAMES) - 1)];

        return "{$first} {$last}";
    }

    private function nextEmail(string $branch): string
    {
        $this->memberSequence++;

        return sprintf('genealogy.%s.%05d@gwtrack.com', $branch, $this->memberSequence);
    }

    private function seedUser(
        string $email,
        string $name,
        string $rankCode,
        string $role,
        ?User $sponsor,
        int $joinedMonthsAgo,
        ?User $mentor = null,
        bool $isActive = true,
    ): User {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('Password123'),
                'rank_id' => $this->rankIds[$rankCode] ?? $this->rankIds['TA'] ?? null,
                'team_id' => $this->team->id,
                'sponsor_id' => $sponsor?->id,
                'mentor_id' => $mentor?->id,
                'is_active' => $isActive,
                'joined_at' => now()->subMonths($joinedMonthsAgo),
                'last_login_at' => now()->subDays(max(1, $joinedMonthsAgo % 14)),
            ],
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }

    private function seedProfiles(): void
    {
        $countries = [
            ['Canada', 'Vancouver', 'Canada Pacific Time'],
            ['United States', 'Phoenix', 'MST'],
            ['Canada', 'Toronto', 'Canada Eastern Time'],
            ['United States', 'Dallas', 'CST'],
        ];

        User::query()
            ->where('team_id', $this->team->id)
            ->orderBy('id')
            ->get()
            ->each(function (User $user, int $index) use ($countries): void {
                [$countryName, $city, $timezoneCode] = $countries[$index % count($countries)];

                Profile::updateOrCreate(
                    ['user_id' => $user->id],
                    LocationOptions::profileAttributesForStorage([
                        'phone' => '555-01'.str_pad((string) ($index % 100), 2, '0', STR_PAD_LEFT),
                        'city' => $city,
                        'country_id' => LocationOptions::resolveCountryId($countryName),
                        'timezone_id' => LocationOptions::resolveTimezoneId($timezoneCode),
                        'license_number' => $index % 3 === 0 ? 'LIC-'.$user->id.'-GW' : null,
                        'gw_associate_id' => 'GW-'.$user->id,
                        'is_gw_active_associate' => true,
                        'recruited_at' => $user->joined_at,
                    ])
                );
            });
    }
}
