<div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        @foreach ([
            ['key' => 'standard', 'title' => 'Report an issue', 'desc' => 'Bug, access, data, or performance problem', 'accent' => 'border-red-200 bg-red-50/80'],
            ['key' => 'enhancement', 'title' => 'Suggest an enhancement', 'desc' => 'Wishlist idea to improve GWTrack', 'accent' => 'border-sky-200 bg-sky-50/80'],
            ['key' => 'documentation', 'title' => 'Browse documentation', 'desc' => 'User guides for each GWTrack module', 'accent' => 'border-emerald-200 bg-emerald-50/80'],
        ] as $option)
            <button
                type="button"
                wire:click="selectTrack(@js($option['key']))"
                @class([
                    'rounded-lg border-2 p-4 text-left transition duration-200 ease-in-out sm:p-5',
                    'border-gw-accent bg-gw-accent-soft shadow-md ring-2 ring-gw-accent/25' => $track === $option['key'],
                    'border-slate-200 bg-white hover:border-gw-accent/50 hover:shadow-sm' => $track !== $option['key'],
                ])
            >
                <span @class([
                    'mb-2 inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                    'bg-gw-primary text-gw-accent' => $track === $option['key'],
                    'bg-slate-100 text-slate-600' => $track !== $option['key'],
                ])>
                    {{ $option['key'] === 'standard' ? 'Support' : ($option['key'] === 'enhancement' ? 'Ideas' : 'Guides') }}
                </span>
                <span class="block text-sm font-bold text-gw-primary">{{ $option['title'] }}</span>
                <span class="mt-1 block text-xs leading-5 text-slate-600">{{ $option['desc'] }}</span>
            </button>
        @endforeach
    </div>

    @if ($track === 'documentation')
        <div>
            <h2 class="text-lg font-semibold text-gw-primary">Module documentation</h2>
            <p class="mt-1 text-sm text-slate-600">Open a user guide or jump directly into the module you are learning.</p>

            <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Module</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">About</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">User guide</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Open module</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($documentationModules as $doc)
                            <tr class="hover:bg-gw-accent-soft/40">
                                <td class="px-4 py-3 font-semibold text-gw-primary">{{ $doc['module'] }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $doc['summary'] }}</td>
                                <td class="px-4 py-3">
                                    @if (! empty($doc['slug']))
                                        <a
                                            href="{{ route('support.documentation', $doc['slug']) }}"
                                            class="inline-flex items-center gap-1 font-semibold text-gw-accent-dark underline decoration-gw-accent underline-offset-2 hover:text-gw-primary hover:decoration-gw-accent-dark"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            View guide
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3" /></svg>
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">Use module link →</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if (! empty($doc['app_route']) && Route::has($doc['app_route']))
                                        <a
                                            href="{{ route($doc['app_route']) }}"
                                            class="inline-flex rounded-md border border-gw-accent/40 bg-gw-accent-soft px-3 py-1.5 text-xs font-semibold text-gw-primary hover:bg-gw-accent-muted"
                                        >
                                            {{ $doc['app_label'] ?? 'Open' }}
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($track === 'enhancement')
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gw-primary">Enhancement details</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Feature title</label>
                    <input type="text" wire:model="feature_title" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary focus:border-gw-accent focus:ring-2 focus:ring-gw-accent/40">
                    @error('feature_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Related module</label>
                    <select wire:model="related_module" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary focus:border-gw-accent focus:ring-2 focus:ring-gw-accent/40">
                        <option value="">Select module</option>
                        @foreach ($modules as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('related_module') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Your priority</label>
                    <select wire:model="user_priority" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary focus:border-gw-accent focus:ring-2 focus:ring-gw-accent/40">
                        @foreach ($wishlistPriorities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">What problem would this solve?</label>
                    <textarea wire:model="problem_solved" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary focus:border-gw-accent focus:ring-2 focus:ring-gw-accent/40"></textarea>
                    @error('problem_solved') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Describe your idea</label>
                    <textarea wire:model="suggested_description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary focus:border-gw-accent focus:ring-2 focus:ring-gw-accent/40"></textarea>
                    @error('suggested_description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Business value (select all that apply)</label>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach ($businessValueOptions as $key => $label)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                                <input type="checkbox" wire:model="business_value" value="{{ $key }}" class="rounded border-slate-400 text-gw-accent focus:ring-gw-accent/40">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Example link (optional)</label>
                    <input type="url" wire:model="example_link" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary focus:border-gw-accent focus:ring-2 focus:ring-gw-accent/40">
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Attachments (optional)</label>
                    <input type="file" wire:model="attachments" multiple class="mt-1 w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-gw-primary file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                    <div wire:loading wire:target="attachments" class="mt-1 text-xs text-gw-accent-dark">Uploading…</div>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" wire:click="submit" class="rounded-lg bg-gw-primary px-5 py-3 text-sm font-semibold text-white transition duration-200 ease-in-out hover:bg-gw-primary-hover">
                    Submit enhancement
                </button>
            </div>
        </div>
    @else
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gw-primary">Step {{ $step }} of {{ $maxStep }}</h2>
            <div class="flex gap-1">
                @for ($i = 1; $i <= $maxStep; $i++)
                    <span @class(['h-1.5 w-8 rounded-full', $i <= $step ? 'bg-gw-accent' : 'bg-slate-200'])></span>
                @endfor
            </div>
        </div>

        @if ($step === 1)
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Issue type</label>
                    <select wire:model="type" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary focus:border-gw-accent focus:ring-2 focus:ring-gw-accent/40">
                        <option value="">Select type</option>
                        @foreach ($ticketTypes as $key => $label)
                            @if ($key !== 'enhancement')
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Module</label>
                    <select wire:model="module" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary focus:border-gw-accent focus:ring-2 focus:ring-gw-accent/40">
                        <option value="">Select module</option>
                        @foreach ($modules as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('module') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
                    <select wire:model="category" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary focus:border-gw-accent focus:ring-2 focus:ring-gw-accent/40">
                        <option value="">Select category</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        @elseif ($step === 2)
            <div class="grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">What were you trying to do?</label>
                        <select wire:model="user_intent_action" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary">
                            <option value="">Select action</option>
                            @foreach ($intentActions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('user_intent_action') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">What happened?</label>
                        <select wire:model="user_reported_outcome" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary">
                            <option value="">Select outcome</option>
                            @foreach ($outcomes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('user_reported_outcome') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Short subject</label>
                    <input type="text" wire:model="subject" maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary">
                    @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Describe what happened</label>
                    <textarea wire:model="description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        @elseif ($step === 3)
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['urgency', 'Urgency', $urgencyLevels],
                    ['impact', 'Impact', $impactLevels],
                    ['frequency', 'Frequency', $frequencyLevels],
                    ['device', 'Device', $devices],
                    ['browser', 'Browser', $browsers],
                ] as [$field, $label, $options])
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</label>
                        <select wire:model="{{ $field }}" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary">
                            @foreach ($options as $key => $optionLabel)
                                <option value="{{ $key }}">{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                        @error($field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Page URL (optional)</label>
                    <input type="url" wire:model="related_url" class="mt-1 w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-gw-primary">
                </div>
            </div>
        @elseif ($step === 4)
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Screenshots or files (optional, max 10MB each)</label>
                <input type="file" wire:model="attachments" multiple class="mt-1 w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-gw-primary file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                <div wire:loading wire:target="attachments" class="mt-1 text-xs text-gw-accent-dark">Uploading…</div>
                @error('attachments.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        @elseif ($step === 5)
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div><dt class="text-xs uppercase text-slate-500">Type</dt><dd class="font-semibold text-gw-primary">{{ $ticketTypes[$type] ?? $type }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Module</dt><dd class="font-semibold text-gw-primary">{{ $modules[$module] ?? $module }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Category</dt><dd class="font-semibold text-gw-primary">{{ $categories[$category] ?? $category }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Urgency</dt><dd class="font-semibold text-gw-primary">{{ $urgencyLevels[$urgency] ?? $urgency }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs uppercase text-slate-500">Subject</dt><dd class="font-semibold text-gw-primary">{{ $subject }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs uppercase text-slate-500">Description</dt><dd class="text-gw-primary">{{ $description }}</dd></div>
                </dl>
            </div>
        @endif

        <div class="mt-6 flex justify-between">
            @if ($step > 1)
                <button type="button" wire:click="previousStep" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</button>
            @else
                <span></span>
            @endif
            @if ($step < $maxStep)
                <button type="button" wire:click="nextStep" class="rounded-lg bg-gw-primary px-5 py-3 text-sm font-semibold text-white hover:bg-gw-primary-hover">Continue</button>
            @else
                <button type="button" wire:click="submit" class="rounded-lg bg-gw-primary px-5 py-3 text-sm font-semibold text-white hover:bg-gw-primary-hover">Submit ticket</button>
            @endif
        </div>
    @endif
</div>
