<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rebrands stored content from "GWTrack" to "GWTracks".
 *
 * The codebase, resource PDFs and config were rebranded directly; this brings
 * database-held content (resource titles/bodies, checklist labels, notification
 * and e-mail template text, demo account addresses) in line with it.
 *
 * Deliberately untouched:
 *   - migrations.migration -> must keep matching the on-disk migration filename
 *     (0000_00_00_000001_create_gwtrack_core_tables.php), which is not renamed
 *     because the migration has already run.
 *   - jobs.payload -> serialised, transient queue data.
 */
return new class extends Migration
{
    /** Columns that are never rewritten. */
    private const SKIP = [
        'migrations.migration',
        'jobs.payload',
    ];

    /** singular => plural, per capitalisation variant. */
    private const VARIANTS = [
        'GWTRACK' => 'GWTRACKS',
        'GWTrack' => 'GWTracks',
        'Gwtrack' => 'Gwtracks',
        'gwtrack' => 'gwtracks',
    ];

    public function up(): void
    {
        foreach ($this->targetColumns() as [$table, $column]) {
            DB::statement(sprintf(
                'UPDATE `%s` SET `%s` = %s WHERE `%s` LIKE %s',
                $table,
                $column,
                $this->pluraliseExpression($column),
                $column,
                DB::getPdo()->quote('%gwtrack%'),
            ));
        }
    }

    public function down(): void
    {
        foreach ($this->targetColumns() as [$table, $column]) {
            $expression = sprintf('`%s`', $column);

            foreach (self::VARIANTS as $singular => $plural) {
                $expression = sprintf(
                    'REPLACE(%s, %s, %s)',
                    $expression,
                    DB::getPdo()->quote($plural),
                    DB::getPdo()->quote($singular),
                );
            }

            DB::statement(sprintf(
                'UPDATE `%s` SET `%s` = %s WHERE `%s` LIKE %s',
                $table,
                $column,
                $expression,
                $column,
                DB::getPdo()->quote('%gwtracks%'),
            ));
        }
    }

    /** Column types that can hold brand text. */
    private const TEXT_TYPES = [
        'varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext', 'json', 'jsonb',
    ];

    /**
     * Every text-bearing column in the current schema, minus the skip list.
     *
     * Resolved at run time through Laravel's schema introspection rather than
     * information_schema, so this behaves the same on MySQL and on the sqlite
     * database the test suite migrates against.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function targetColumns(): array
    {
        $targets = [];

        foreach (Schema::getTables() as $table) {
            $name = $table['name'];

            foreach (Schema::getColumns($name) as $column) {
                $type = strtolower($column['type_name'] ?? '');

                if (! in_array($type, self::TEXT_TYPES, true)) {
                    continue;
                }

                if (in_array($name.'.'.$column['name'], self::SKIP, true)) {
                    continue;
                }

                $targets[] = [$name, $column['name']];
            }
        }

        return $targets;
    }

    /**
     * Builds a REPLACE() chain that appends the plural "s" without ever
     * double-pluralising text that is already correct.
     *
     * Existing plurals are parked on control-character sentinels first, so a
     * value holding both spellings converts cleanly and re-running is a no-op.
     */
    private function pluraliseExpression(string $column): string
    {
        $expression = sprintf('`%s`', $column);
        $sentinels = [];
        $index = 1;

        // Park the already-correct spellings.
        foreach (self::VARIANTS as $plural) {
            $sentinel = chr($index++);
            $sentinels[$sentinel] = $plural;

            $expression = sprintf(
                'REPLACE(%s, %s, %s)',
                $expression,
                DB::getPdo()->quote($plural),
                DB::getPdo()->quote($sentinel),
            );
        }

        // Pluralise the remaining singulars.
        foreach (self::VARIANTS as $singular => $plural) {
            $expression = sprintf(
                'REPLACE(%s, %s, %s)',
                $expression,
                DB::getPdo()->quote($singular),
                DB::getPdo()->quote($plural),
            );
        }

        // Restore the parked spellings.
        foreach ($sentinels as $sentinel => $plural) {
            $expression = sprintf(
                'REPLACE(%s, %s, %s)',
                $expression,
                DB::getPdo()->quote($sentinel),
                DB::getPdo()->quote($plural),
            );
        }

        return $expression;
    }
};
