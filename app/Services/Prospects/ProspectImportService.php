<?php

namespace App\Services\Prospects;

use App\Models\Prospect;
use App\Models\ProspectImport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProspectImportService
{
    public function __construct(private ProspectFunnelService $funnels) {}

    /**
     * @return array{headers: list<string>, rows: list<array<string, string|null>>}
     */
    public function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to read CSV file.');
        }

        $headers = [];
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === []) {
                $headers = array_map(fn ($value) => trim((string) $value), $data);

                continue;
            }

            if ($this->rowIsEmpty($data)) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = isset($data[$index]) ? trim((string) $data[$index]) : null;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @param  array<string, string|null>  $columnMap
     * @return list<array<string, mixed>>
     */
    public function mapRows(array $rows, array $columnMap): array
    {
        $mapped = [];

        foreach ($rows as $index => $row) {
            $entry = ['row_number' => $index + 1];

            foreach (config('prospects.import_columns', []) as $field) {
                $csvColumn = $columnMap[$field] ?? null;
                $entry[$field] = $csvColumn && isset($row[$csvColumn])
                    ? trim((string) $row[$csvColumn]) ?: null
                    : null;
            }

            if (filled($entry['first_name'] ?? null)) {
                $mapped[] = $entry;
            }
        }

        return $mapped;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{duplicates: list<array<string, mixed>>, unique: list<array<string, mixed>>}
     */
    public function detectDuplicates(User $user, array $rows): array
    {
        $existing = Prospect::query()
            ->where('owner_id', $user->id)
            ->whereNull('deleted_at')
            ->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        $emailIndex = $existing
            ->filter(fn (Prospect $prospect) => filled($prospect->email))
            ->mapWithKeys(fn (Prospect $prospect) => [Str::lower($prospect->email) => $prospect]);

        $phoneIndex = $existing
            ->filter(fn (Prospect $prospect) => filled($prospect->phone))
            ->mapWithKeys(fn (Prospect $prospect) => [$this->normalizePhone($prospect->phone) => $prospect]);

        $duplicates = [];
        $unique = [];

        foreach ($rows as $row) {
            $match = null;
            $matchField = null;

            if (filled($row['email'] ?? null)) {
                $match = $emailIndex->get(Str::lower((string) $row['email']));
                $matchField = 'email';
            }

            if (! $match && filled($row['phone'] ?? null)) {
                $normalized = $this->normalizePhone((string) $row['phone']);
                $match = $phoneIndex->get($normalized);
                $matchField = 'phone';
            }

            if ($match) {
                $duplicates[] = [
                    ...$row,
                    'matched_prospect_id' => $match->id,
                    'matched_prospect_name' => $match->displayName(),
                    'matched_on' => $matchField,
                ];
            } else {
                $unique[] = $row;
            }
        }

        return [
            'duplicates' => $duplicates,
            'unique' => $unique,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{imported: int, skipped: int, duplicate: int}
     */
    public function import(User $user, ProspectImport $import, array $rows, bool $skipDuplicates = true): array
    {
        $detection = $this->detectDuplicates($user, $rows);
        $toImport = $skipDuplicates ? $detection['unique'] : $rows;

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($user, $import, $toImport, $rows, $detection, $skipDuplicates, &$imported, &$skipped): void {
            foreach ($toImport as $row) {
                if (! $skipDuplicates) {
                    $isDuplicate = collect($detection['duplicates'])->contains(
                        fn (array $duplicate): bool => ($duplicate['row_number'] ?? null) === ($row['row_number'] ?? null)
                    );

                    if ($isDuplicate) {
                        $skipped++;

                        continue;
                    }
                }

                $attributes = $this->attributesFromRow($row);
                $this->funnels->createProspect($user, $attributes);
                $imported++;
            }

            $import->update([
                'status' => 'completed',
                'total_rows' => count($rows),
                'imported_rows' => $imported,
                'skipped_rows' => $skipped,
                'duplicate_rows' => count($detection['duplicates']),
                'duplicate_payload' => $detection['duplicates'],
                'completed_at' => now(),
            ]);
        });

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'duplicate' => count($detection['duplicates']),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function attributesFromRow(array $row): array
    {
        $funnelType = $row['funnel_type'] ?? 'insurance';

        if (! in_array($funnelType, ['insurance', 'recruiting', 'both'], true)) {
            $funnelType = 'insurance';
        }

        $attributes = [
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'] ?? null,
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'city' => $row['city'] ?? null,
            'funnel_type' => $funnelType,
            'interest_level' => 'warm',
            'priority' => 'medium',
            'status' => 'active',
        ];

        if (filled($row['source'] ?? null)) {
            $sourceId = DB::table('prospect_sources')
                ->where('is_active', true)
                ->where(function ($query) use ($row): void {
                    $query->where('name', $row['source'])
                        ->orWhere('slug', Str::slug((string) $row['source']));
                })
                ->value('id');

            if ($sourceId) {
                $attributes['prospect_source_id'] = $sourceId;
            }
        }

        return $attributes;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
