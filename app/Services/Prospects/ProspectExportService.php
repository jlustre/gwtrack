<?php

namespace App\Services\Prospects;

use App\Models\Prospect;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProspectExportService
{
    /**
     * @return StreamedResponse
     */
    public function streamForUser(User $user): StreamedResponse
    {
        $columns = config('prospects.export_columns', []);
        $filename = 'prospects-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($user, $columns): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $columns);

            Prospect::query()
                ->with(['source:id,name', 'stage:id,name'])
                ->where('owner_id', $user->id)
                ->whereNull('deleted_at')
                ->orderBy('created_at')
                ->chunk(200, function ($prospects) use ($handle, $columns): void {
                    foreach ($prospects as $prospect) {
                        fputcsv($handle, $this->rowForProspect($prospect, $columns));
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  list<string>  $columns
     * @return list<string|null>
     */
    private function rowForProspect(Prospect $prospect, array $columns): array
    {
        $values = [
            'id' => $prospect->id,
            'first_name' => $prospect->first_name,
            'last_name' => $prospect->last_name,
            'email' => $prospect->email,
            'phone' => $prospect->phone,
            'city' => $prospect->city,
            'state' => $prospect->state_province,
            'funnel_type' => $prospect->funnel_type,
            'stage' => $prospect->stage?->name,
            'interest_level' => $prospect->interest_level,
            'source' => $prospect->source?->name,
            'status' => $prospect->status,
            'created_at' => $prospect->created_at?->toDateTimeString(),
        ];

        return array_map(fn (string $column) => $values[$column] ?? null, $columns);
    }
}
