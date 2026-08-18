# Backup and Restore Module Architecture

## 1. Module Overview

The Backup and Restore module is a super-admin-only operations area for creating, downloading, validating, restoring, and auditing database backups. It supports four backup scopes:

- `structural`: setup, master, configuration, user, role, permission, and lookup data.
- `transactional`: day-to-day business records, activity records, documents, messages, notifications, reports, and logs.
- `combined`: structural and transactional tables together.
- `full`: every approved application table except hard-excluded runtime/cache/session tables.

The module should be implemented as a Livewire-backed admin area under `/admin/backups`, using the existing Laravel 13, Livewire 3, Tailwind CSS, and Spatie Permission stack.

Core safety principles:

- Backups are ZIP archives stored outside the public directory.
- Uploaded restore archives are validated before any data mutation.
- Restore uses a table whitelist from `config/backup-classification.php`.
- Restore never executes arbitrary SQL from an uploaded file.
- Restore creates an automatic pre-restore backup first.
- Every backup and restore action writes audit metadata with user, IP address, user agent, timestamp, and result.

## 2. Database Schema

### `backups`

Tracks each backup request and generated backup archive.

```php
Schema::create('backups', function (Blueprint $table) {
    $table->id();
    $table->string('backup_type', 40)->index();
    $table->string('filename');
    $table->string('file_path')->nullable();
    $table->unsignedBigInteger('file_size')->default(0);
    $table->string('status', 40)->default('pending')->index();
    $table->json('included_tables')->nullable();
    $table->json('excluded_tables')->nullable();
    $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->text('error_message')->nullable();
    $table->json('metadata')->nullable();
    $table->text('notes')->nullable();
    $table->boolean('compressed')->default(true);
    $table->boolean('encrypted')->default(false);
    $table->string('disk')->default('local');
    $table->timestamps();
});
```

### `backup_items`

Stores per-table backup details and record counts.

```php
Schema::create('backup_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('backup_id')->constrained('backups')->cascadeOnDelete();
    $table->string('table_name')->index();
    $table->unsignedBigInteger('record_count')->default(0);
    $table->string('status', 40)->default('pending')->index();
    $table->unsignedBigInteger('exported_bytes')->default(0);
    $table->text('error_message')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

### `restore_logs`

Tracks restore attempts, including uploaded files and backups restored from history.

```php
Schema::create('restore_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('backup_id')->nullable()->constrained('backups')->nullOnDelete();
    $table->foreignId('pre_restore_backup_id')->nullable()->constrained('backups')->nullOnDelete();
    $table->string('restore_type', 40)->index();
    $table->string('restore_mode', 40)->index();
    $table->string('status', 40)->default('pending')->index();
    $table->foreignId('restored_by')->constrained('users')->cascadeOnDelete();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->text('error_message')->nullable();
    $table->json('restored_tables')->nullable();
    $table->json('metadata')->nullable();
    $table->string('source_filename')->nullable();
    $table->string('source_file_path')->nullable();
    $table->timestamps();
});
```

### `restore_items`

Stores per-table restore results.

```php
Schema::create('restore_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('restore_log_id')->constrained('restore_logs')->cascadeOnDelete();
    $table->string('table_name')->index();
    $table->unsignedBigInteger('records_before')->default(0);
    $table->unsignedBigInteger('records_restored')->default(0);
    $table->unsignedBigInteger('records_skipped')->default(0);
    $table->string('status', 40)->default('pending')->index();
    $table->text('error_message')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

### `backup_schedules`

Stores recurring automatic backup definitions.

```php
Schema::create('backup_schedules', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('backup_type', 40);
    $table->string('frequency', 40);
    $table->time('run_at')->nullable();
    $table->json('days_of_week')->nullable();
    $table->json('included_tables')->nullable();
    $table->json('excluded_tables')->nullable();
    $table->boolean('compressed')->default(true);
    $table->boolean('encrypted')->default(false);
    $table->unsignedInteger('retention_days')->default(30);
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_run_at')->nullable();
    $table->timestamp('next_run_at')->nullable();
    $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
    $table->timestamps();
});
```

### `audit_logs`

Use an existing audit table if the application already has one. If not, add a generic audit table:

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('event');
    $table->string('auditable_type')->nullable();
    $table->unsignedBigInteger('auditable_id')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

## 3. Table Classification Strategy

Create `config/backup-classification.php`:

```php
<?php

return [
    'structural_tables' => [
        'users',
        'profiles',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'countries',
        'state_provinces',
        'timezones',
        'ranks',
        'rank_requirements',
        'prospect_types',
        'prospect_sources',
        'prospect_interests',
        'prospect_funnel_stages',
        'pipeline_stages',
        'checklist_types',
        'checklists',
        'checklist_instructions',
        'task_categories',
        'tasks',
        'email_templates',
        'email_template_tokens',
        'notification_types',
        'notification_channels',
        'notification_templates',
        'notification_triggers',
    ],

    'transactional_tables' => [
        'prospects',
        'prospect_activities',
        'prospect_notes',
        'prospect_files',
        'prospect_follow_ups',
        'prospect_appointments',
        'prospect_conversions',
        'calendar_events',
        'calendar_event_attendees',
        'task_users',
        'task_user_comments',
        'task_user_checklist_items',
        'fna_records',
        'fna_activity_logs',
        'fna_attachments',
        'conversations',
        'messages',
        'message_attachments',
        'notifications',
        'notification_delivery_logs',
        'support_tickets',
        'support_ticket_comments',
        'member_production_entries',
        'audit_logs',
    ],

    'excluded_tables' => [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'migrations',
        'password_reset_tokens',
        'sessions',
        'personal_access_tokens',
    ],

    'protected_tables' => [
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
    ],
];
```

The classification service should intersect configured tables with actual database tables, then remove `excluded_tables`. For custom table selection, the selected table names must be part of the approved whitelist.

## 4. Backup Workflow

1. Super admin opens Create Backup.
2. UI resolves the default table list from the selected backup type.
3. User optionally includes or excludes specific approved tables.
4. Controller or Livewire component validates request and creates a `backups` record with `pending` status.
5. `CreateBackupJob` is dispatched.
6. Job marks backup as `processing` and emits `BackupStarted`.
7. `BackupService` exports each table into structured JSON files, not raw SQL.
8. Service creates `metadata.json` with app version, database name, included tables, excluded tables, record counts, checksum, and creator metadata.
9. `BackupStorageService` ZIP-compresses the working directory.
10. If encryption is enabled, encrypt the archive before final storage.
11. Backup record is updated with file path, size, metadata, completion time, and `completed` status.
12. Job emits `BackupCompleted` and sends notification.
13. Failures update status to `failed`, store an error message, emit `BackupFailed`, and write audit logs.

Recommended archive format:

```text
gwtrack-backup-20260702-153000.zip
  metadata.json
  tables/
    users.json
    roles.json
    prospects.json
  checksums.json
```

Table files should contain:

```json
{
  "table": "users",
  "columns": ["id", "name", "email", "created_at", "updated_at"],
  "primary_key": "id",
  "records": []
}
```

## 5. Restore Workflow

1. Super admin uploads a `.zip` backup archive.
2. Restore page validates file size, MIME type, structure, metadata, checksums, and table whitelist.
3. UI shows backup type, app version, created date, included tables, and record counts.
4. User chooses restore type, restore mode, and tables.
5. User confirms warning modal and enters password.
6. System verifies password with `Hash::check`.
7. `RestoreBackupJob` creates an automatic pre-restore backup.
8. Job marks restore log as `processing` and emits `RestoreStarted`.
9. App enters maintenance mode for high-risk restores when configured.
10. Restore service validates current schema compatibility before mutation.
11. Service restores selected tables using structured data and query builder operations only.
12. Foreign key checks are disabled only inside the controlled restore block and always re-enabled in `finally`.
13. Cache is cleared, and Spatie permission cache is reset.
14. Restore log is marked `completed`, `completed_with_warnings`, or `failed`.
15. Result summary lists restored, skipped, failed, and protected tables.

Restore modes:

- `replace`: truncate selected table, then insert records.
- `merge`: upsert records by primary key or configured unique keys.
- `skip_duplicates`: insert only records whose primary key or unique key does not exist.

Rollback policy:

- Use a transaction per table where possible.
- Use the pre-restore backup as the disaster recovery rollback point.
- Avoid claiming whole-database rollback for operations that include DDL or very large tables.

## 6. UI Page Structure

### Backup Dashboard

Route: `/admin/backups`

Cards:

- Total backups.
- Last successful backup.
- Failed backups.
- Total backup storage used.
- Next scheduled backup.

Panels:

- Recent backup jobs.
- Recent restore jobs.
- Storage retention status.
- Scheduled backup health.

### Create Backup

Route: `/admin/backups/create`

Controls:

- Backup type segmented selector.
- Approved table checklist with search.
- Include all structural or transactional shortcut.
- Exclude table checklist.
- Compression toggle.
- Encryption toggle.
- Notes textarea.
- Create Backup button.
- Livewire progress status.

### Backup History

Route: `/admin/backups/history`

Controls:

- Search by filename or creator.
- Filters by type, status, user, and date.
- Table with type, filename, size, status, creator, date, and actions.
- Actions: download, view metadata, restore, delete.

### Restore

Route: `/admin/backups/restore`

Controls:

- Upload backup ZIP.
- Validate button.
- Preview panel with metadata and record counts.
- Restore type selector.
- Restore mode selector.
- Table checklist.
- High-risk warning.
- Password confirmation.
- Restore button.
- Progress panel.

### Restore History

Route: `/admin/backups/restores`

Table:

- Restore type.
- Restore mode.
- Status.
- Restored by.
- Restored tables.
- Started date.
- Completed date.
- Error message.
- View details action.

## 7. Laravel Folder Structure

```text
app/
  Events/
    BackupStarted.php
    BackupCompleted.php
    BackupFailed.php
    RestoreStarted.php
    RestoreCompleted.php
    RestoreFailed.php
  Http/
    Controllers/
      Admin/
        BackupDownloadController.php
    Middleware/
      EnsureBackupAdmin.php
  Jobs/
    CreateBackupJob.php
    RestoreBackupJob.php
    CleanupOldBackupsJob.php
  Livewire/
    Admin/
      Backups/
        BackupDashboard.php
        CreateBackup.php
        BackupHistory.php
        RestoreBackup.php
        RestoreHistory.php
  Models/
    Backup.php
    BackupItem.php
    BackupSchedule.php
    RestoreLog.php
    RestoreItem.php
    AuditLog.php
  Notifications/
    BackupCompletedNotification.php
    BackupFailedNotification.php
    RestoreCompletedNotification.php
    RestoreFailedNotification.php
  Policies/
    BackupPolicy.php
    RestoreLogPolicy.php
  Services/
    Backups/
      BackupService.php
      RestoreService.php
      BackupClassificationService.php
      BackupValidationService.php
      BackupStorageService.php
      BackupAuditService.php
config/
  backup-classification.php
database/
  migrations/
resources/
  views/
    livewire/
      admin/
        backups/
```

## 8. Security Controls

- Gate module access with `role:super-admin` and `permission:manage backups`.
- Require password confirmation before restore.
- Enforce CSRF protection through standard Laravel web middleware.
- Validate uploaded file extension, MIME type, size, ZIP structure, metadata, and checksums.
- Store backups on a private disk such as `storage/app/private/backups`.
- Never symlink backup storage into `public`.
- Use signed, authorized download routes instead of public URLs.
- Optionally encrypt archives with Laravel encryption or libsodium-backed encryption.
- Validate all table names against `BackupClassificationService::approvedTables()`.
- Export and restore JSON records through query builder operations, not arbitrary SQL execution.
- Log every action with user ID, IP address, user agent, timestamp, selected tables, and outcome.
- Reject archives with unknown tables, missing metadata, checksum mismatch, app incompatibility, or schema mismatch.
- Keep `protected_tables` disabled by default for restore unless the user explicitly selects them and passes an additional confirmation.

## 9. Validation Rules

Create backup request:

```php
[
    'backup_type' => ['required', Rule::in(['structural', 'transactional', 'combined', 'full'])],
    'included_tables' => ['array'],
    'included_tables.*' => ['string', new ApprovedBackupTable],
    'excluded_tables' => ['array'],
    'excluded_tables.*' => ['string', new ApprovedBackupTable],
    'compressed' => ['boolean'],
    'encrypted' => ['boolean'],
    'notes' => ['nullable', 'string', 'max:2000'],
]
```

Restore request:

```php
[
    'backup_file' => ['required', 'file', 'mimes:zip', 'max:512000'],
    'restore_type' => ['required', Rule::in(['structural', 'transactional', 'combined', 'selected'])],
    'restore_mode' => ['required', Rule::in(['replace', 'merge', 'skip_duplicates'])],
    'tables' => ['required', 'array', 'min:1'],
    'tables.*' => ['string', new ApprovedBackupTable],
    'password' => ['required', 'current_password'],
]
```

## 10. Service Class Design

### `BackupClassificationService`

Responsibilities:

- Return structural, transactional, combined, full, excluded, protected, and approved table lists.
- Validate a table selection.
- Resolve backup type to a table set.

### `BackupService`

Responsibilities:

- Create backup working directory.
- Export selected tables to JSON.
- Count records.
- Create metadata.
- Create checksums.
- Delegate ZIP and encryption to storage service.
- Update `backups` and `backup_items`.

### `RestoreService`

Responsibilities:

- Restore selected tables from validated JSON.
- Apply restore mode.
- Create `restore_items`.
- Handle foreign key checks.
- Clear application and permission cache after success.

### `BackupValidationService`

Responsibilities:

- Inspect uploaded ZIP.
- Parse metadata.
- Verify checksums.
- Verify table whitelist.
- Verify app and schema compatibility.
- Return a preview DTO for the restore UI.

### `BackupStorageService`

Responsibilities:

- Store backup ZIP files on private disk.
- Generate safe filenames.
- Encrypt and decrypt archives when enabled.
- Stream authorized downloads.
- Delete expired backups.

## 11. Queue Job Design

### `CreateBackupJob`

- Implements `ShouldQueue`.
- Uses a small timeout for structural backups and a larger timeout for full backups.
- Updates progress after every table.
- Marks the backup failed on exception.
- Sends completion or failure notification.

### `RestoreBackupJob`

- Implements `ShouldQueue`.
- Creates pre-restore backup.
- Validates archive again inside the job.
- Restores selected tables.
- Always re-enables foreign key checks.
- Clears caches.
- Sends completion or failure notification.

### `CleanupOldBackupsJob`

- Runs daily from `routes/console.php`.
- Deletes backups older than schedule-level or global retention days.
- Keeps failed restore pre-backups for a longer incident window.

Console schedule:

```php
Schedule::job(new CleanupOldBackupsJob)->dailyAt('02:30');
```

## 12. Sample Migrations

Create one migration for the core tables:

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('backup_type', 40)->index();
            $table->string('filename');
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('status', 40)->default('pending')->index();
            $table->json('included_tables')->nullable();
            $table->json('excluded_tables')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('compressed')->default(true);
            $table->boolean('encrypted')->default(false);
            $table->string('disk')->default('local');
            $table->timestamps();
        });

        Schema::create('backup_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_id')->constrained('backups')->cascadeOnDelete();
            $table->string('table_name')->index();
            $table->unsignedBigInteger('record_count')->default(0);
            $table->string('status', 40)->default('pending')->index();
            $table->unsignedBigInteger('exported_bytes')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('restore_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_id')->nullable()->constrained('backups')->nullOnDelete();
            $table->foreignId('pre_restore_backup_id')->nullable()->constrained('backups')->nullOnDelete();
            $table->string('restore_type', 40)->index();
            $table->string('restore_mode', 40)->index();
            $table->string('status', 40)->default('pending')->index();
            $table->foreignId('restored_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('restored_tables')->nullable();
            $table->json('metadata')->nullable();
            $table->string('source_filename')->nullable();
            $table->string('source_file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('restore_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restore_log_id')->constrained('restore_logs')->cascadeOnDelete();
            $table->string('table_name')->index();
            $table->unsignedBigInteger('records_before')->default(0);
            $table->unsignedBigInteger('records_restored')->default(0);
            $table->unsignedBigInteger('records_skipped')->default(0);
            $table->string('status', 40)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
};
```

## 13. Sample Models

```php
class Backup extends Model
{
    protected $fillable = [
        'backup_type',
        'filename',
        'file_path',
        'file_size',
        'status',
        'included_tables',
        'excluded_tables',
        'created_by',
        'started_at',
        'completed_at',
        'error_message',
        'metadata',
        'notes',
        'compressed',
        'encrypted',
        'disk',
    ];

    protected $casts = [
        'included_tables' => 'array',
        'excluded_tables' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'compressed' => 'boolean',
        'encrypted' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(BackupItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

```php
class RestoreLog extends Model
{
    protected $fillable = [
        'backup_id',
        'pre_restore_backup_id',
        'restore_type',
        'restore_mode',
        'status',
        'restored_by',
        'started_at',
        'completed_at',
        'error_message',
        'restored_tables',
        'metadata',
        'source_filename',
        'source_file_path',
    ];

    protected $casts = [
        'restored_tables' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(RestoreItem::class);
    }
}
```

## 14. Sample Service Code

```php
final class BackupClassificationService
{
    public function tablesFor(string $type): array
    {
        return match ($type) {
            'structural' => $this->approved(config('backup-classification.structural_tables', [])),
            'transactional' => $this->approved(config('backup-classification.transactional_tables', [])),
            'combined' => $this->approved([
                ...config('backup-classification.structural_tables', []),
                ...config('backup-classification.transactional_tables', []),
            ]),
            'full' => $this->approved($this->databaseTables()),
            default => throw new InvalidArgumentException("Unknown backup type [{$type}]."),
        };
    }

    public function approvedTables(): array
    {
        return array_values(array_diff(
            $this->databaseTables(),
            config('backup-classification.excluded_tables', [])
        ));
    }

    public function validateTables(array $tables): array
    {
        $approved = $this->approvedTables();

        return array_values(array_filter(array_unique($tables), fn ($table) => in_array($table, $approved, true)));
    }

    private function approved(array $tables): array
    {
        return $this->validateTables($tables);
    }

    private function databaseTables(): array
    {
        return collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->values()
            ->all();
    }
}
```

```php
final class BackupService
{
    public function create(Backup $backup): Backup
    {
        $backup->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $tables = $backup->included_tables ?? [];
        $workingPath = storage_path("app/backups/work/{$backup->id}");
        File::ensureDirectoryExists("{$workingPath}/tables");

        $recordCounts = [];

        foreach ($tables as $table) {
            $rows = DB::table($table)->orderBy($this->primaryKey($table) ?? 'id')->get();
            $payload = [
                'table' => $table,
                'columns' => Schema::getColumnListing($table),
                'primary_key' => $this->primaryKey($table),
                'records' => $rows,
            ];

            File::put("{$workingPath}/tables/{$table}.json", json_encode($payload, JSON_PRETTY_PRINT));

            $recordCounts[$table] = $rows->count();

            $backup->items()->create([
                'table_name' => $table,
                'record_count' => $rows->count(),
                'status' => 'completed',
            ]);
        }

        File::put("{$workingPath}/metadata.json", json_encode([
            'backup_type' => $backup->backup_type,
            'created_by' => $backup->created_by,
            'created_at' => now()->toIso8601String(),
            'included_tables' => $tables,
            'app_version' => config('app.version'),
            'database_name' => DB::connection()->getDatabaseName(),
            'record_counts' => $recordCounts,
        ], JSON_PRETTY_PRINT));

        return app(BackupStorageService::class)->storeWorkingDirectory($backup, $workingPath);
    }

    private function primaryKey(string $table): ?string
    {
        $index = collect(DB::select("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'"))->first();

        return $index?->Column_name;
    }
}
```

```php
final class RestoreService
{
    public function restore(RestoreLog $restoreLog, ValidatedBackupArchive $archive): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($restoreLog->restored_tables as $table) {
                DB::transaction(function () use ($restoreLog, $archive, $table): void {
                    $payload = $archive->tablePayload($table);
                    $recordsBefore = DB::table($table)->count();
                    $restored = 0;
                    $skipped = 0;

                    if ($restoreLog->restore_mode === 'replace') {
                        DB::table($table)->truncate();
                    }

                    foreach ($payload['records'] as $record) {
                        $result = $this->restoreRecord($table, (array) $record, $payload['primary_key'], $restoreLog->restore_mode);
                        $restored += $result === 'restored' ? 1 : 0;
                        $skipped += $result === 'skipped' ? 1 : 0;
                    }

                    $restoreLog->items()->create([
                        'table_name' => $table,
                        'records_before' => $recordsBefore,
                        'records_restored' => $restored,
                        'records_skipped' => $skipped,
                        'status' => 'completed',
                    ]);
                });
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        Artisan::call('cache:clear');

        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    private function restoreRecord(string $table, array $record, ?string $primaryKey, string $mode): string
    {
        if ($mode === 'merge' && $primaryKey && array_key_exists($primaryKey, $record)) {
            DB::table($table)->updateOrInsert([$primaryKey => $record[$primaryKey]], $record);

            return 'restored';
        }

        if ($mode === 'skip_duplicates' && $primaryKey && array_key_exists($primaryKey, $record)) {
            if (DB::table($table)->where($primaryKey, $record[$primaryKey])->exists()) {
                return 'skipped';
            }
        }

        DB::table($table)->insert($record);

        return 'restored';
    }
}
```

## 15. Sample Livewire Page Structure

```php
final class CreateBackup extends Component
{
    public string $backupType = 'structural';
    public array $tables = [];
    public array $selectedTables = [];
    public array $excludedTables = [];
    public bool $compressed = true;
    public bool $encrypted = false;
    public ?string $notes = null;

    public function mount(BackupClassificationService $classification): void
    {
        $this->tables = $classification->tablesFor($this->backupType);
        $this->selectedTables = $this->tables;
    }

    public function updatedBackupType(BackupClassificationService $classification): void
    {
        $this->tables = $classification->tablesFor($this->backupType);
        $this->selectedTables = $this->tables;
        $this->excludedTables = [];
    }

    public function createBackup(BackupClassificationService $classification): void
    {
        $validated = $this->validate([
            'backupType' => ['required', Rule::in(['structural', 'transactional', 'combined', 'full'])],
            'selectedTables' => ['required', 'array', 'min:1'],
            'excludedTables' => ['array'],
            'compressed' => ['boolean'],
            'encrypted' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tables = array_values(array_diff(
            $classification->validateTables($validated['selectedTables']),
            $classification->validateTables($validated['excludedTables'])
        ));

        $backup = Backup::create([
            'backup_type' => $validated['backupType'],
            'filename' => app(BackupStorageService::class)->makeFilename($validated['backupType']),
            'status' => 'pending',
            'included_tables' => $tables,
            'excluded_tables' => $validated['excludedTables'],
            'created_by' => auth()->id(),
            'compressed' => $validated['compressed'],
            'encrypted' => $validated['encrypted'],
            'notes' => $validated['notes'],
        ]);

        CreateBackupJob::dispatch($backup);

        session()->flash('status', 'Backup queued successfully.');
    }

    public function render(): View
    {
        return view('livewire.admin.backups.create-backup');
    }
}
```

Blade page layout should use existing admin panels, compact metric cards, table filters, and modal confirmation patterns already present in the app.

## 16. Routes

Add inside the authenticated admin route group:

```php
Route::prefix('admin/backups')
    ->name('admin.backups.')
    ->middleware(['role:super-admin', 'permission:manage backups'])
    ->group(function (): void {
        Route::get('/', \App\Livewire\Admin\Backups\BackupDashboard::class)->name('index');
        Route::get('/create', \App\Livewire\Admin\Backups\CreateBackup::class)->name('create');
        Route::get('/history', \App\Livewire\Admin\Backups\BackupHistory::class)->name('history');
        Route::get('/restore', \App\Livewire\Admin\Backups\RestoreBackup::class)->name('restore');
        Route::get('/restores', \App\Livewire\Admin\Backups\RestoreHistory::class)->name('restores');
        Route::get('/{backup}/download', \App\Http\Controllers\Admin\BackupDownloadController::class)
            ->can('download', 'backup')
            ->name('download');
    });
```

## 17. Permissions

Recommended permissions:

- `view backups`
- `create backups`
- `download backups`
- `delete backups`
- `restore backups`
- `manage backup schedules`
- `manage backups`

Assign all backup permissions only to `super-admin`. The `manage backups` permission can be used as the route-level umbrella permission, while granular permissions are used for buttons and policies.

Seeder sketch:

```php
$permissions = [
    'view backups',
    'create backups',
    'download backups',
    'delete backups',
    'restore backups',
    'manage backup schedules',
    'manage backups',
];

foreach ($permissions as $permission) {
    Permission::findOrCreate($permission);
}

Role::findByName('super-admin')->givePermissionTo($permissions);
```

## 18. Testing Checklist

Feature tests:

- Super admin can view backup dashboard.
- Non-super-admin cannot view backup dashboard.
- Create backup validates invalid table names.
- Create backup creates a `pending` record and dispatches job.
- Download route denies unauthorized users.
- Restore upload rejects non-ZIP files.
- Restore upload rejects ZIP files without metadata.
- Restore upload rejects unknown tables.
- Restore requires current password.
- Restore creates pre-restore backup before mutation.
- Restore replace mode truncates and inserts expected rows.
- Restore merge mode updates existing rows.
- Restore skip duplicate mode leaves existing rows unchanged.
- Failed restore re-enables foreign key checks.
- Permission cache is cleared after restoring permission tables.
- Cleanup job deletes expired backup files and records.

Unit tests:

- Classification service resolves all backup types correctly.
- Classification service excludes protected runtime tables.
- Validation service detects checksum mismatch.
- Storage service stores files outside public path.
- Storage service generates unique safe filenames.

Manual QA:

- Large backup progress updates correctly.
- Failed backup displays actionable error message.
- Restore preview matches metadata.
- Warning modal cannot be bypassed.
- Backup history filters work together.
- Restore history detail view shows per-table outcomes.

## 19. Edge Cases

- Large tables exceed memory: export and restore with cursor or chunked reads.
- Tables without `id`: detect primary keys via database metadata; fall back to insert-only restore.
- Circular foreign keys: restore structural tables before transactional tables and use controlled foreign key disabling.
- Protected user/role restoration: require separate confirmation and keep the current super-admin account safe.
- Schema drift: reject archives whose table columns are missing from the current schema unless a compatibility map exists.
- App version mismatch: allow minor version restores only if schema hash matches.
- Encrypted archive with missing key: fail validation with a clear message.
- ZIP slip paths: reject archive entries containing `../`, absolute paths, or invalid table filenames.
- Partial restore failure: mark per-table item failed, mark restore failed, and point to pre-restore backup.
- Queue worker death: stale `processing` records should be visible as interrupted and retryable only by super admin.
- Public disk misconfiguration: refuse to write backups to disks with public visibility.
- Uploaded documents and files: database backup should track file references; binary file backup can be a future extension.

## 20. Deployment Checklist

Before release:

- Add migrations and run on staging.
- Add `config/backup-classification.php` and review table classification.
- Add private backup disk to `config/filesystems.php`.
- Add backup retention settings to `.env.example`.
- Seed backup permissions for `super-admin`.
- Add Livewire pages and routes.
- Add policies and middleware.
- Configure queue worker and scheduler.
- Configure storage permissions for `storage/app/private/backups`.
- Confirm backups are blocked from public web access.
- Run full feature and unit test suite.
- Perform staging backup and restore rehearsal.
- Document restore runbook for emergencies.
- Verify offsite/cloud backup destination if enabled.
- Verify monitoring alerts for failed scheduled backups.

Recommended environment variables:

```env
BACKUP_DISK=local
BACKUP_RETENTION_DAYS=30
BACKUP_MAX_UPLOAD_MB=500
BACKUP_ENCRYPTION_ENABLED=false
BACKUP_REQUIRE_MAINTENANCE_MODE=true
```

## Implementation Notes for GWTrack

- Use the existing `/admin` route group and Spatie role middleware.
- Prefer Livewire pages over traditional form controllers because this app already uses Livewire heavily for admin-style workflows.
- Treat FNA, prospects, calendar, messaging, notifications, support, goals, training progress, and production records as transactional by default.
- Treat roles, permissions, users, profiles, ranks, checklist definitions, task definitions, email templates, notification templates, and lookup tables as structural by default.
- Keep runtime tables such as `sessions`, `jobs`, `failed_jobs`, `cache`, and `migrations` excluded.
- Add a top-level admin navigation item visible only to `super-admin`.
