<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;



class MessageCenterAnnouncement extends Model

{

    use SoftDeletes;



    protected $table = 'message_center_announcements';



    protected $fillable = [

        'category_id',

        'title',

        'slug',

        'summary',

        'body',

        'priority',

        'status',

        'is_pinned',

        'is_featured',

        'featured_sort',

        'requires_acknowledgement',

        'audience_type',

        'audience_config',

        'tags',

        'metadata',

        'campaign_id',

        'calendar_event_id',

        'hero_image_path',

        'view_count',

        'published_at',

        'expires_at',

        'scheduled_at',

        'created_by',

    ];



    protected function casts(): array

    {

        return [

            'category_id' => 'integer',

            'campaign_id' => 'integer',

            'calendar_event_id' => 'integer',

            'audience_config' => 'array',

            'tags' => 'array',

            'metadata' => 'array',

            'is_pinned' => 'boolean',

            'is_featured' => 'boolean',

            'featured_sort' => 'integer',

            'requires_acknowledgement' => 'boolean',

            'view_count' => 'integer',

            'published_at' => 'datetime',

            'expires_at' => 'datetime',

            'scheduled_at' => 'datetime',

        ];

    }



    public function getRouteKeyName(): string

    {

        return 'slug';

    }



    public function category(): BelongsTo

    {

        return $this->belongsTo(AnnouncementCategory::class, 'category_id');

    }



    public function creator(): BelongsTo

    {

        return $this->belongsTo(User::class, 'created_by');

    }



    public function reads(): HasMany

    {

        return $this->hasMany(MessageCenterAnnouncementRead::class, 'announcement_id');

    }



    public function attachments(): HasMany

    {

        return $this->hasMany(AnnouncementAttachment::class, 'announcement_id');

    }



    public function acknowledgements(): HasMany

    {

        return $this->hasMany(AnnouncementAcknowledgement::class, 'announcement_id');

    }



    public function bookmarks(): HasMany

    {

        return $this->hasMany(AnnouncementBookmark::class, 'announcement_id');

    }



    public function reactions(): HasMany

    {

        return $this->hasMany(AnnouncementReaction::class, 'announcement_id');

    }



    public function comments(): HasMany

    {

        return $this->hasMany(AnnouncementComment::class, 'announcement_id');

    }



    public function campaign(): BelongsTo

    {

        return $this->belongsTo(AnnouncementCampaign::class, 'campaign_id');

    }



    public function calendarEvent(): BelongsTo

    {

        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');

    }



    public function scopePublished(Builder $query): Builder

    {

        return $query

            ->where('status', 'published')

            ->whereNotNull('published_at')

            ->where('published_at', '<=', now());

    }



    public function scopeVisible(Builder $query): Builder

    {

        return $query->where(function (Builder $inner): void {

            $inner->whereNull('expires_at')

                ->orWhere('expires_at', '>', now());

        });

    }



    public function isPublished(): bool

    {

        return $this->status === 'published'

            && $this->published_at !== null

            && $this->published_at->lte(now());

    }

}

