<x-app-layout>
    <section
        x-data="cfmManagement(@js(array_merge($cfmManagementPayload, [
            'assignUrl' => route('team.cfms.assign'),
            'addCfmUrl' => route('team.cfms.store'),
            'licensedJurisdictionsUrlTemplate' => route('team.cfms.licensed-jurisdictions.update', ['user' => '__CFM__']),
            'openAssignModal' => $openAssignModal,
            'openAddCfmModal' => (bool) session('open_add_cfm_modal', false),
            'openCfmProfilePanel' => $openCfmProfilePanel ?? false,
            'openCfmLicensedEdit' => $openCfmLicensedEdit ?? false,
            'focusCfmId' => $focusCfmId ?? null,
            'oldLicensedJurisdictions' => old('licensed_jurisdictions', []),
            'oldAssignAssociateId' => old('associate_id'),
            'oldAssignCfmId' => old('cfm_id'),
        ])))"
        x-init="init()"
        wire:ignore
        class="cfm-management-page space-y-6"
    >
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
            @include('team.cfms.partials.header')
        </div>

        @include('team.cfms.partials.flash')

        <div class="space-y-6">
            @include('team.cfms.partials.stats')
            @include('team.cfms.partials.rank-structure')
            @include('team.cfms.partials.legend')
            @include('team.cfms.partials.filters')
            @include('team.cfms.partials.view-tabs')
            @include('team.cfms.partials.table')
            @include('team.cfms.partials.cards')
            @include('team.cfms.partials.compare')
            @include('team.cfms.partials.ai-panel')
            @include('team.cfms.partials.empty-state')
        </div>

        @include('team.cfms.partials.profile-panel')
        @include('team.cfms.partials.assign-modal')
        @include('team.cfms.partials.fap-queue-modal')
        @include('team.cfms.partials.add-cfm-modal')
        @include('team.cfms.partials.export-modal')
    </section>
</x-app-layout>
