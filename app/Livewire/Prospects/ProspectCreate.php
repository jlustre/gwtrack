<?php

namespace App\Livewire\Prospects;

use App\Livewire\Concerns\ManagesProspectDependents;
use App\Models\Prospect;
use App\Services\Prospects\ProspectFunnelService;
use App\Support\ProspectDependents;
use App\Support\ProspectFormRules;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProspectCreate extends Component
{
    use ManagesProspectDependents;

    public string $first_name = '';

    public string $last_name = '';

    public ?string $preferred_name = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $home_phone = null;

    public ?string $work_phone = null;

    public ?string $address_line_1 = null;

    public ?string $city = null;

    public ?string $state_province = null;

    public ?string $country = null;

    public ?string $postal_code = null;

    public ?string $date_of_birth = null;

    public ?string $occupation = null;

    public ?string $employer_business = null;

    public ?string $gender = null;

    public ?string $marital_status = null;

    public ?string $spouse_name = null;

    public ?string $spouse_occupation = null;

    public ?string $spouse_date_of_birth = null;

    /** @var list<array{name: string, age: int|null}> */
    public array $dependents = [];

    public ?string $qualification_notes = null;

    public string $funnel_type = 'insurance';

    public ?int $prospect_funnel_id = null;

    public ?int $prospect_source_id = null;

    public ?int $pipeline_stage_id = null;

    public string $interest_level = 'warm';

    public ?int $interest_score = null;

    public string $priority = 'medium';

    public ?string $fna_status = 'not_started';

    public ?string $referral_source_name = null;

    public ?string $campaign_name = null;

    public ?string $next_follow_up_at = null;

    public ?string $follow_up_notes = null;

    /** @var list<string> */
    public array $qualification_traits = [];

    public bool $embedded = false;

    public bool $quick = false;

    public bool $modal = false;

    public bool $setupAvailable = true;

    public function mount(ProspectFunnelService $funnels, bool $embedded = false, bool $quick = false, bool $modal = false): void
    {

        $this->authorize('create', Prospect::class);

        $this->embedded = $embedded;
        $this->quick = $quick;
        $this->modal = $modal;

        if (request()->query('funnel_type') === 'recruiting') {

            $this->funnel_type = 'recruiting';

        }

        try {
            $defaultFunnel = $funnels->resolveFunnel($this->funnel_type);
        } catch (ModelNotFoundException) {
            $this->setupAvailable = false;
            $this->dependents = ProspectDependents::formRows(null);

            return;
        }

        $this->prospect_funnel_id = $defaultFunnel->id;

        $this->pipeline_stage_id = $funnels->numberedStagesForFunnel($defaultFunnel->id)[0]['id'] ?? null;

        $this->dependents = ProspectDependents::formRows(null);

    }

    public function updatedFunnelType(string $value, ProspectFunnelService $funnels): void
    {

        $funnel = $funnels->resolveFunnel($value);

        $this->prospect_funnel_id = $funnel->id;

        $this->pipeline_stage_id = $funnels->numberedStagesForFunnel($funnel->id)[0]['id'] ?? null;

    }

    public function save(ProspectFunnelService $funnels): void
    {

        $this->authorize('create', Prospect::class);

        if (! $this->setupAvailable) {
            $this->addError('setup', 'Prospect funnel setup is not available yet.');

            return;
        }

        $validated = ProspectFormRules::normalizeProfileAttributes($this->validate($this->rules()));

        $prospect = $funnels->createProspect(auth()->user(), $validated);

        if ($this->modal) {
            $this->dispatch(
                'dashboard-prospect-created',
                prospectName: trim($prospect->first_name.' '.$prospect->last_name),
            );

            return;
        }

        if ($this->quick) {
            session()->flash('prospect_quick_add_status', trim($prospect->first_name.' '.$prospect->last_name).' was added to your prospects.');

            $this->redirectRoute('team.prospects.screen', 'prospects', navigate: true);

            return;
        }

        session()->flash('status', 'Prospect created successfully.');

        $this->redirectRoute('team.prospects.records.show', $prospect, navigate: true);

    }

    public function render(ProspectFunnelService $funnels): View
    {

        return view('livewire.prospects.prospect-create', [

            'funnels' => $funnels->funnelsForSelect(),

            'sources' => DB::table('prospect_sources')->where('is_active', true)->orderBy('sort_order')->get(),

            'stages' => $this->setupAvailable ? $funnels->numberedStagesForFunnel($this->prospect_funnel_id) : [],

            'funnelTypes' => config('prospects.funnel_types'),

            'fnaStatuses' => config('prospects.fna_statuses'),

        ]);

    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {

        if ($this->quick) {
            return $this->quickRules();
        }

        return array_merge([

            'first_name' => ['required', 'string', 'max:255'],

            'last_name' => ['nullable', 'string', 'max:255'],

            'preferred_name' => ['nullable', 'string', 'max:255'],

            'email' => ['nullable', 'email', 'max:255'],

            'phone' => ['nullable', 'string', 'max:60'],

            'home_phone' => ['nullable', 'string', 'max:60'],

            'work_phone' => ['nullable', 'string', 'max:60'],

            'address_line_1' => ['nullable', 'string', 'max:255'],

            'city' => ['nullable', 'string', 'max:255'],

            'state_province' => ['nullable', 'string', 'max:255'],

            'country' => ['nullable', 'string', 'max:255'],

            'postal_code' => ['nullable', 'string', 'max:40'],

            'funnel_type' => ['required', 'in:insurance,recruiting,both'],

            'prospect_funnel_id' => ['required', 'exists:prospect_funnels,id'],

            'prospect_source_id' => ['nullable', 'exists:prospect_sources,id'],

            'pipeline_stage_id' => ['nullable', 'exists:pipeline_stages,id'],

            'interest_level' => ['required', 'in:cold,warm,hot'],

            'interest_score' => ['nullable', 'integer', 'min:1', 'max:10'],

            'priority' => ['required', 'in:low,medium,high,urgent'],

            'fna_status' => ['nullable', 'string', 'max:40'],

            'referral_source_name' => ['nullable', 'string', 'max:255'],

            'campaign_name' => ['nullable', 'string', 'max:255'],

            'next_follow_up_at' => ['nullable', 'date'],

        ], ProspectFormRules::profileRules());

    }

    /**
     * @return array<string, mixed>
     */
    private function quickRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'funnel_type' => ['required', 'in:insurance,recruiting,both'],
            'prospect_funnel_id' => ['required', 'exists:prospect_funnels,id'],
            'prospect_source_id' => ['nullable', 'exists:prospect_sources,id'],
            'pipeline_stage_id' => ['nullable', 'exists:pipeline_stages,id'],
            'interest_level' => ['required', 'in:cold,warm,hot'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
        ];
    }
}
