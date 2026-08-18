<?php

namespace App\Models;

use App\Services\NewMemberRegistrationService;
use App\Support\SystemTaskAssignor;
use App\Support\UserAvatar;
use App\Support\UserRankRoleLabel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use HasRoles;
    use MustVerifyEmailTrait;
    use Notifiable;
    use SoftDeletes;

    public const DEFAULT_ROLE = 'member';

    protected $fillable = [
        'name',
        'email',
        'password',
        'rank_id',
        'team_id',
        'sponsor_id',
        'mentor_id',
        'is_active',
        'joined_at',
        'last_login_at',
        'last_login_ip',
        'is_online',
        'messaging_suspended_at',
        'messaging_suspended_by',
        'messaging_suspension_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'joined_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_online' => 'boolean',
            'messaging_suspended_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            $user->ensureDefaultRole();
        });

        static::deleting(function (User $user): void {
            $relatedRecords = [
                $user->profile(),
                $user->registrationInvitations(),
                $user->mentorAssignments(),
                $user->apprenticeshipAssignments(),
            ];

            foreach ($relatedRecords as $relation) {
                $user->isForceDeleting()
                    ? $relation->withTrashed()->forceDelete()
                    : $relation->delete();
            }
        });
    }

    public function isSystemUser(): bool
    {
        return SystemTaskAssignor::isSystemAssignor($this->id)
            || $this->email === SystemTaskAssignor::EMAIL;
    }

    /**
     * Ensure every account has a baseline role (member, or system for the system user).
     */
    public function ensureDefaultRole(): void
    {
        if ($this->roles()->exists()) {
            return;
        }

        if ($this->isSystemUser()) {
            $this->assignRole(Role::findOrCreate('system'));

            return;
        }

        $this->assignRole(Role::findOrCreate(self::DEFAULT_ROLE));
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function sponsoredMembers(): HasMany
    {
        return $this->hasMany(User::class, 'sponsor_id');
    }

    public function ancestorPaths(): HasMany
    {
        return $this->hasMany(UserHierarchyPath::class, 'descendant_id');
    }

    public function descendantPaths(): HasMany
    {
        return $this->hasMany(UserHierarchyPath::class, 'ancestor_id');
    }

    public function sponsorRelationships(): HasMany
    {
        return $this->hasMany(SponsorRelationship::class, 'sponsor_id');
    }

    public function sponsoredRelationship(): HasOne
    {
        return $this->hasOne(SponsorRelationship::class, 'member_id')->where('status', 'active');
    }

    public function teamVisibilityGrants(): HasMany
    {
        return $this->hasMany(TeamVisibilityPermission::class, 'viewer_id');
    }

    public function teamVisibilityGrantedToOthers(): HasMany
    {
        return $this->hasMany(TeamVisibilityPermission::class, 'visible_user_id');
    }

    public function registrationInvitations(): HasMany
    {
        return $this->hasMany(RegistrationInvitation::class, 'sponsor_id');
    }

    public function favoritePortalResources(): BelongsToMany
    {
        return $this->belongsToMany(PortalResource::class, 'resource_favorites', 'user_id', 'resource_id')
            ->withTimestamps()
            ->withTrashed();
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function messagingSuspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'messaging_suspended_by');
    }

    public function isMessagingSuspended(): bool
    {
        return $this->messaging_suspended_at !== null;
    }

    public function mentorAssignments(): HasMany
    {
        return $this->hasMany(MentorAssignment::class, 'mentor_id');
    }

    public function checklistTypeStarts(): HasMany
    {
        return $this->hasMany(UserChecklistTypeStart::class);
    }

    public function apprenticeshipAssignments(): HasMany
    {
        return $this->hasMany(MentorAssignment::class, 'apprentice_id');
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class, 'owner_id');
    }

    public function prospectSharesGranted(): HasMany
    {
        return $this->hasMany(ProspectShare::class, 'granted_by');
    }

    public function prospectSharesReceived(): HasMany
    {
        return $this->hasMany(ProspectShare::class, 'shared_with');
    }

    public function bookingEventTypes(): HasMany
    {
        return $this->hasMany(BookingEventType::class, 'owner_id');
    }

    public function availabilitySchedules(): HasMany
    {
        return $this->hasMany(AvailabilitySchedule::class);
    }

    public function bookingLinks(): HasMany
    {
        return $this->hasMany(BookingLink::class, 'owner_id');
    }

    public function cfmBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'cfm_id');
    }

    public function traineeBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'trainee_id');
    }

    public function calendarPreference(): HasOne
    {
        return $this->hasOne(UserCalendarPreference::class);
    }

    public function calendarScheduleBlocks(): HasMany
    {
        return $this->hasMany(CalendarScheduleBlock::class);
    }

    public function calendarScheduleBlockOverrides(): HasMany
    {
        return $this->hasMany(CalendarScheduleBlockOverride::class);
    }

    public function cfmMentorProfile(): HasOne
    {
        return $this->hasOne(CfmMentorProfile::class);
    }

    public function apprentices(): HasMany
    {
        return $this->hasMany(User::class, 'mentor_id');
    }

    public function canAccessCfmManagement(): bool
    {
        if ($this->hasAnyRole(['super-admin', 'admin', 'agency-owner'])) {
            return true;
        }

        return $this->rank?->code === 'ED';
    }

    public function canAccessCfmPortal(): bool
    {
        if ($this->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        return $this->hasRole('certified-field-mentor');
    }

    public function canViewAnnouncements(): bool
    {
        return $this->hasAnyPermission(['view announcements', 'manage announcements']);
    }

    public function canManageDocuments(): bool
    {
        return $this->hasAnyRole([
            'super-admin',
            'admin',
            'agency-owner',
            'certified-field-mentor',
        ]);
    }

    public function canDeleteDocuments(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin']);
    }

    public function canUpdateDocumentSeeder(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin']);
    }

    public function canUpdateDocument(?object $record = null): bool
    {
        if ($this->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        if (! $this->canManageDocuments()) {
            return false;
        }

        if ($record === null) {
            return true;
        }

        $createdBy = $record->created_by ?? null;

        return $createdBy !== null && (int) $createdBy === (int) $this->id;
    }

    public function initials(): string
    {
        return UserAvatar::initials($this->name);
    }

    public function topbarRankRoleLabel(string $fallback = 'Portal User'): string
    {
        return UserRankRoleLabel::for($this, $fallback);
    }

    public function profilePhotoUrl(): ?string
    {
        return UserAvatar::urlForUser($this);
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')->latest();
    }

    public function sendEmailVerificationNotification(): void
    {
        app(NewMemberRegistrationService::class)->sendEmailVerificationEmail($this);
    }
}
