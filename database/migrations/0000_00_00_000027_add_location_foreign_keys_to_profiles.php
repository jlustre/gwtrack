<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('city')->constrained()->nullOnDelete();
            $table->foreignId('state_province_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
            $table->foreignId('timezone_id')->nullable()->after('state_province_id')->constrained()->nullOnDelete();
        });

        if (Schema::hasColumn('profiles', 'country')) {
            foreach (DB::table('profiles')->get() as $profile) {
                $countryId = filled($profile->country)
                    ? DB::table('countries')->where('name', $profile->country)->value('id')
                    : null;

                $stateProvinceId = null;

                if ($countryId && filled($profile->province)) {
                    $stateProvinceId = DB::table('state_provinces')
                        ->where('country_id', $countryId)
                        ->where('name', $profile->province)
                        ->value('id');
                }

                $timezoneId = null;

                if (filled($profile->timezone)) {
                    $timezoneId = DB::table('timezones')
                        ->where(function ($query) use ($profile): void {
                            $query->where('code', $profile->timezone)
                                ->orWhere('name', $profile->timezone);
                        })
                        ->value('id');
                }

                DB::table('profiles')->where('id', $profile->id)->update([
                    'country_id' => $countryId,
                    'state_province_id' => $stateProvinceId,
                    'timezone_id' => $timezoneId,
                ]);
            }
        }

        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'province')) {
                $table->dropColumn('province');
            }

            if (Schema::hasColumn('profiles', 'country')) {
                $table->dropColumn('country');
            }

            if (Schema::hasColumn('profiles', 'timezone')) {
                $table->dropColumn('timezone');
            }
        });

        if (Schema::hasTable('profile_completion_fields')) {
            $fieldKeyMap = [
                'country' => 'country_id',
                'province' => 'state_province_id',
                'timezone' => 'timezone_id',
            ];

            foreach ($fieldKeyMap as $oldKey => $newKey) {
                DB::table('profile_completion_fields')
                    ->where('field_key', $oldKey)
                    ->update(['field_key' => $newKey]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('province')->nullable()->after('phone');
            $table->string('country')->nullable()->after('city');
            $table->string('timezone')->nullable()->after('country');
        });

        foreach (DB::table('profiles')->get() as $profile) {
            $country = $profile->country_id
                ? DB::table('countries')->where('id', $profile->country_id)->value('name')
                : null;
            $province = $profile->state_province_id
                ? DB::table('state_provinces')->where('id', $profile->state_province_id)->value('name')
                : null;
            $timezone = $profile->timezone_id
                ? DB::table('timezones')->where('id', $profile->timezone_id)->value('code')
                : null;

            DB::table('profiles')->where('id', $profile->id)->update([
                'country' => $country,
                'province' => $province,
                'timezone' => $timezone,
            ]);
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('timezone_id');
            $table->dropConstrainedForeignId('state_province_id');
            $table->dropConstrainedForeignId('country_id');
        });

        if (Schema::hasTable('profile_completion_fields')) {
            $fieldKeyMap = [
                'country_id' => 'country',
                'state_province_id' => 'province',
                'timezone_id' => 'timezone',
            ];

            foreach ($fieldKeyMap as $oldKey => $newKey) {
                DB::table('profile_completion_fields')
                    ->where('field_key', $oldKey)
                    ->update(['field_key' => $newKey]);
            }
        }
    }
};
