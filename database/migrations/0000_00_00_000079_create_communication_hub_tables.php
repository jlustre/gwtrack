<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcement_categories')) {
            Schema::create('announcement_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('icon', 80)->nullable();
                $table->string('color', 20)->nullable();
                $table->string('default_priority', 20)->default('informational');
                $table->boolean('requires_acknowledgement_default')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('message_center_announcements')) {
            Schema::table('message_center_announcements', function (Blueprint $table) {
                if (! Schema::hasColumn('message_center_announcements', 'category_id')) {
                    $table->foreignId('category_id')->nullable()->after('id')->constrained('announcement_categories')->nullOnDelete();
                }
                if (! Schema::hasColumn('message_center_announcements', 'slug')) {
                    $table->string('slug')->nullable()->unique()->after('title');
                }
                if (! Schema::hasColumn('message_center_announcements', 'summary')) {
                    $table->string('summary', 500)->nullable()->after('slug');
                }
                if (! Schema::hasColumn('message_center_announcements', 'priority')) {
                    $table->string('priority', 20)->default('informational')->after('summary');
                }
                if (! Schema::hasColumn('message_center_announcements', 'status')) {
                    $table->string('status', 20)->default('draft')->after('priority');
                }
                if (! Schema::hasColumn('message_center_announcements', 'is_pinned')) {
                    $table->boolean('is_pinned')->default(false)->after('status');
                }
                if (! Schema::hasColumn('message_center_announcements', 'is_featured')) {
                    $table->boolean('is_featured')->default(false)->after('is_pinned');
                }
                if (! Schema::hasColumn('message_center_announcements', 'requires_acknowledgement')) {
                    $table->boolean('requires_acknowledgement')->default(false)->after('is_featured');
                }
                if (! Schema::hasColumn('message_center_announcements', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable()->after('published_at');
                }
                if (! Schema::hasColumn('message_center_announcements', 'scheduled_at')) {
                    $table->timestamp('scheduled_at')->nullable()->after('expires_at');
                }
                if (! Schema::hasColumn('message_center_announcements', 'tags')) {
                    $table->json('tags')->nullable()->after('audience_config');
                }
                if (! Schema::hasColumn('message_center_announcements', 'metadata')) {
                    $table->json('metadata')->nullable()->after('tags');
                }
                if (! Schema::hasColumn('message_center_announcements', 'hero_image_path')) {
                    $table->string('hero_image_path')->nullable()->after('metadata');
                }
                if (! Schema::hasColumn('message_center_announcements', 'view_count')) {
                    $table->unsignedInteger('view_count')->default(0)->after('hero_image_path');
                }
            });
        }

        if (! Schema::hasTable('announcement_attachments')) {
            Schema::create('announcement_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('announcement_id')->constrained('message_center_announcements')->cascadeOnDelete();
                $table->string('label')->nullable();
                $table->string('file_path');
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('announcement_acknowledgements')) {
            Schema::create('announcement_acknowledgements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('announcement_id')->constrained('message_center_announcements')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamp('acknowledged_at');
                $table->timestamps();

                $table->unique(['announcement_id', 'user_id'], 'announcement_ack_user_uq');
            });
        }

        $this->migrateLegacyAnnouncements();
        $this->backfillSlugsAndStatus();
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_acknowledgements');
        Schema::dropIfExists('announcement_attachments');

        if (Schema::hasTable('message_center_announcements')) {
            Schema::table('message_center_announcements', function (Blueprint $table) {
                foreach ([
                    'category_id', 'slug', 'summary', 'priority', 'status', 'is_pinned',
                    'is_featured', 'requires_acknowledgement', 'expires_at', 'scheduled_at',
                    'tags', 'metadata', 'hero_image_path', 'view_count',
                ] as $column) {
                    if (Schema::hasColumn('message_center_announcements', $column)) {
                        if ($column === 'category_id') {
                            $table->dropConstrainedForeignId('category_id');
                        } else {
                            $table->dropColumn($column);
                        }
                    }
                }
            });
        }

        Schema::dropIfExists('announcement_categories');
    }

    private function migrateLegacyAnnouncements(): void
    {
        if (! Schema::hasTable('announcements') || ! Schema::hasTable('message_center_announcements')) {
            return;
        }

        $legacyRows = DB::table('announcements')->whereNull('deleted_at')->get();

        foreach ($legacyRows as $row) {
            $exists = DB::table('message_center_announcements')
                ->where('title', $row->title)
                ->where('created_by', $row->created_by)
                ->exists();

            if ($exists) {
                continue;
            }

            $slug = Str::slug($row->title).'-'.$row->id;

            DB::table('message_center_announcements')->insert([
                'title' => $row->title,
                'slug' => $slug,
                'body' => $row->body,
                'summary' => Str::limit(strip_tags($row->body), 200),
                'priority' => 'informational',
                'status' => $row->published_at ? 'published' : 'draft',
                'audience_type' => 'all',
                'audience_config' => null,
                'published_at' => $row->published_at,
                'created_by' => $row->created_by,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }
    }

    private function backfillSlugsAndStatus(): void
    {
        if (! Schema::hasTable('message_center_announcements')) {
            return;
        }

        $rows = DB::table('message_center_announcements')->whereNull('slug')->get(['id', 'title', 'published_at', 'status']);

        foreach ($rows as $row) {
            DB::table('message_center_announcements')->where('id', $row->id)->update([
                'slug' => Str::slug($row->title).'-'.$row->id,
                'status' => $row->status ?: ($row->published_at ? 'published' : 'draft'),
            ]);
        }
    }
};
