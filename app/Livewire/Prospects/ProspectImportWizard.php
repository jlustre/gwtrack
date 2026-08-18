<?php

namespace App\Livewire\Prospects;

use App\Models\ProspectImport;
use App\Services\Prospects\ProspectImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class ProspectImportWizard extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public $csvFile;

    public ?int $importId = null;

    /** @var list<string> */
    public array $headers = [];

    /** @var array<string, string|null> */
    public array $columnMap = [];

    /** @var list<array<string, mixed>> */
    public array $previewRows = [];

    /** @var list<array<string, mixed>> */
    public array $mappedRows = [];

    /** @var list<array<string, mixed>> */
    public array $duplicates = [];

    public int $totalRows = 0;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('import prospects'), 403);

        foreach (config('prospects.import_columns', []) as $field) {
            $this->columnMap[$field] = null;
        }
    }

    public function uploadCsv(ProspectImportService $imports): void
    {
        $this->validate([
            'csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $path = $this->csvFile->store('prospect-imports', 'local');
        $parsed = $imports->parseCsv(Storage::disk('local')->path($path));

        $this->headers = $parsed['headers'];
        $this->totalRows = count($parsed['rows']);
        $this->previewRows = array_slice($parsed['rows'], 0, 10);

        $this->autoMapColumns();
        $this->guessColumnMapFromHeaders();

        $import = ProspectImport::create([
            'user_id' => auth()->id(),
            'file_name' => $this->csvFile->getClientOriginalName(),
            'status' => 'preview',
            'total_rows' => $this->totalRows,
            'preview_payload' => [
                'path' => $path,
                'headers' => $this->headers,
                'preview' => $this->previewRows,
            ],
        ]);

        $this->importId = $import->id;
        $this->step = 2;
    }

    public function proceedToDuplicates(ProspectImportService $imports): void
    {
        $this->validateColumnMap();

        $import = $this->importRecord();
        $path = $import->preview_payload['path'] ?? null;

        if (! $path) {
            $this->addError('csvFile', 'Import session expired. Please upload again.');

            return;
        }

        $parsed = $imports->parseCsv(Storage::disk('local')->path($path));
        $this->mappedRows = $imports->mapRows($parsed['rows'], $this->columnMap);
        $detection = $imports->detectDuplicates(auth()->user(), $this->mappedRows);
        $this->duplicates = $detection['duplicates'];

        $import->update([
            'status' => 'duplicates',
            'duplicate_rows' => count($this->duplicates),
            'duplicate_payload' => $this->duplicates,
            'preview_payload' => [
                ...($import->preview_payload ?? []),
                'column_map' => $this->columnMap,
                'mapped_preview' => array_slice($this->mappedRows, 0, 10),
            ],
        ]);

        $this->step = 3;
    }

    public function proceedToConfirm(): void
    {
        $this->step = 4;
    }

    public function confirmImport(ProspectImportService $imports): void
    {
        $import = $this->importRecord();

        $imports->import(auth()->user(), $import, $this->mappedRows, skipDuplicates: true);

        session()->flash('status', 'Import completed successfully.');

        $this->redirectRoute('team.prospects', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.prospects.prospect-import-wizard', [
            'importFields' => config('prospects.import_columns', []),
        ]);
    }

    private function importRecord(): ProspectImport
    {
        return ProspectImport::query()
            ->where('user_id', auth()->id())
            ->findOrFail($this->importId);
    }

    private function validateColumnMap(): void
    {
        $this->validate([
            'columnMap.first_name' => ['required', 'string'],
        ], [
            'columnMap.first_name.required' => 'Map the first name column before continuing.',
        ]);
    }

    private function autoMapColumns(): void
    {
        foreach ($this->headers as $header) {
            $normalized = strtolower(str_replace([' ', '-'], '_', $header));

            foreach (config('prospects.import_columns', []) as $field) {
                if ($this->columnMap[$field] === null && $normalized === $field) {
                    $this->columnMap[$field] = $header;
                }
            }
        }
    }

    private function guessColumnMapFromHeaders(): void
    {
        $aliases = [
            'first_name' => ['first', 'fname', 'given_name'],
            'last_name' => ['last', 'lname', 'surname', 'family_name'],
            'email' => ['email_address', 'e_mail'],
            'phone' => ['mobile', 'cell', 'telephone', 'phone_number'],
            'city' => ['town'],
            'source' => ['lead_source', 'prospect_source'],
            'funnel_type' => ['funnel', 'type'],
        ];

        foreach ($this->headers as $header) {
            $normalized = strtolower(str_replace([' ', '-'], '_', $header));

            foreach ($aliases as $field => $options) {
                if ($this->columnMap[$field] !== null) {
                    continue;
                }

                if (in_array($normalized, $options, true)) {
                    $this->columnMap[$field] = $header;
                }
            }
        }
    }
}
