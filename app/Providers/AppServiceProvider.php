<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Services\Notifications\NotificationOrchestrator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationOrchestrator::class);
        $this->app->singleton(\App\Services\Notifications\NotificationInboxService::class);
        $this->app->singleton(\App\Services\Notifications\NotificationPreferenceService::class);
        $this->app->singleton(\App\Services\Notifications\NotificationRecipientResolver::class);
        $this->app->singleton(\App\Services\Notifications\ChecklistNotificationDispatcher::class);
        $this->app->singleton(\App\Services\Notifications\NotificationEscalationService::class);
        $this->app->singleton(\App\Services\Notifications\NotificationDigestService::class);
        $this->app->singleton(\App\Services\Notifications\CalendarReminderDispatcher::class);
        $this->app->singleton(\App\Services\Communication\CommunicationHubService::class);
        $this->app->singleton(\App\Services\Communication\AnnouncementAudienceResolver::class);
        $this->app->singleton(\App\Services\Communication\AnnouncementEngagementService::class);
        $this->app->singleton(\App\Services\Communication\AnnouncementAcknowledgementService::class);
        $this->app->singleton(\App\Services\Communication\CommunicationSectionService::class);
        $this->app->singleton(\App\Services\Communication\RecognitionService::class);
        $this->app->singleton(\App\Services\Communication\LeadershipDeskService::class);
        $this->app->singleton(\App\Services\Communication\CampaignService::class);
        $this->app->singleton(\App\Services\Communication\AnnouncementEventService::class);
        $this->app->singleton(\App\Services\Communication\BroadcastService::class);
        $this->app->singleton(\App\Services\Communication\AnnouncementAnalyticsService::class);
        $this->app->singleton(\App\Services\Communication\NewsletterGeneratorService::class);
        $this->app->singleton(\App\Services\Communication\CommunicationAiAssistantService::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\MessageCenterAnnouncement::class,
            \App\Policies\AnnouncementPolicy::class,
        );
        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\Backup::class,
            \App\Policies\BackupPolicy::class,
        );
        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\RestoreLog::class,
            \App\Policies\RestoreLogPolicy::class,
        );

        Event::listen(
            \App\Events\Fna\FnaSubmittedForReview::class,
            [\App\Listeners\Fna\NotifyCfmOfFnaSubmission::class, 'handle'],
        );
        Event::listen(
            \App\Events\Fna\FnaSubmittedForReview::class,
            [\App\Listeners\Fna\CreateFnaWorkflowTasks::class, 'handleSubmitted'],
        );
        Event::listen(
            \App\Events\Fna\FnaApproved::class,
            [\App\Listeners\Fna\NotifyAssociateOfFnaApproval::class, 'handle'],
        );
        Event::listen(
            \App\Events\Fna\FnaApproved::class,
            [\App\Listeners\Fna\CreateFnaWorkflowTasks::class, 'handleApproved'],
        );
        Event::listen(
            \App\Events\Fna\FnaRevisionRequested::class,
            [\App\Listeners\Fna\NotifyAssociateOfFnaRevision::class, 'handle'],
        );
        Event::listen(
            \App\Events\Fna\FnaRevisionRequested::class,
            [\App\Listeners\Fna\CreateFnaWorkflowTasks::class, 'handleRevision'],
        );
        Event::listen(
            \App\Events\Fna\FnaSubmittedForReview::class,
            [\App\Listeners\Fna\SyncFnaProspectIntegration::class, 'handleSubmitted'],
        );
        Event::listen(
            \App\Events\Fna\FnaApproved::class,
            [\App\Listeners\Fna\SyncFnaProspectIntegration::class, 'handleApproved'],
        );
        Event::listen(
            \App\Events\Fna\FnaRevisionRequested::class,
            [\App\Listeners\Fna\SyncFnaProspectIntegration::class, 'handleRevision'],
        );
        Event::listen(
            \App\Events\Fna\FnaMeetingScheduled::class,
            [\App\Listeners\Fna\SyncFnaProspectIntegration::class, 'handleMeetingScheduled'],
        );
        Event::listen(
            \App\Events\Fna\FnaSubmittedForReview::class,
            [\App\Listeners\Fna\SyncFnaGoalsBridge::class, 'handleSubmitted'],
        );
        Event::listen(
            \App\Events\Fna\FnaApproved::class,
            [\App\Listeners\Fna\SyncFnaGoalsBridge::class, 'handleApproved'],
        );
        Event::listen(
            \App\Events\Prospects\ProspectConverted::class,
            [\App\Listeners\Prospects\SendProspectConversionNotification::class, 'handle'],
        );
        Event::listen(
            \App\Events\Prospects\ProspectConverted::class,
            [\App\Listeners\Prospects\SyncProspectMemberGoalsBridge::class, 'handle'],
        );
        Event::listen(
            \App\Events\Support\SupportTicketCreated::class,
            [\App\Listeners\Support\SendSupportTicketCreatedNotifications::class, 'handle'],
        );
        Event::listen(
            \App\Events\Support\SupportTicketStatusChanged::class,
            [\App\Listeners\Support\SendSupportTicketStatusChangedNotification::class, 'handle'],
        );
        Event::listen(
            \App\Events\Support\SupportTicketAgentReplied::class,
            [\App\Listeners\Support\SendSupportTicketAgentReplyNotification::class, 'handle'],
        );
        Event::listen(
            \App\Events\Prospects\ProspectStageChanged::class,
            [\App\Listeners\Prospects\SendProspectStageChangedNotification::class, 'handle'],
        );
        Event::listen(
            \Illuminate\Auth\Events\Verified::class,
            [\App\Listeners\HandleNewMemberEmailVerified::class, 'handle'],
        );
    }
}
