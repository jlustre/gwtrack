<div class="space-y-6">
    <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
        <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Management</p>
                <h1 class="mt-2 text-2xl font-semibold">Appointment Calendar</h1>
                <p class="mt-2 text-sm text-slate-200">Schedule prospect appointments and sync them to your main calendar.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" wire:click="openCreateForm()" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">+ Schedule Appointment</button>
                <a href="{{ route('calendar.index') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Main Calendar</a>
                <a href="{{ route('team.prospects') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Dashboard</a>
            </div>
        </div>
    </div>

    @if ($showForm)
        <div class="rounded-lg border border-gw-accent/40 bg-gradient-to-br from-gw-accent-soft to-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gw-primary">{{ $editingAppointmentId ? 'Edit Appointment' : 'New Appointment' }}</h2>
                <button type="button" wire:click="closeForm" class="text-sm font-semibold text-slate-500 hover:text-gw-primary">Cancel</button>
            </div>
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
                <label class="block text-sm font-semibold text-gw-primary md:col-span-2">
                    Prospect
                    <select wire:model="prospectId" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                        <option value="">Select prospect...</option>
                        @foreach ($prospects as $prospect)
                            <option value="{{ $prospect->id }}">{{ $prospect->displayName() }}</option>
                        @endforeach
                    </select>
                    @error('prospectId') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-semibold text-gw-primary">
                    Appointment type
                    <select wire:model="appointmentTypeId" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Select type...</option>
                        @foreach ($appointmentTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-semibold text-gw-primary">
                    Date &amp; time
                    <input type="datetime-local" wire:model="scheduledAt" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                    @error('scheduledAt') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-semibold text-gw-primary">
                    Helper / mentor
                    <select wire:model="assignedHelperId" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        <option value="">None</option>
                        @foreach ($helpers as $helper)
                            <option value="{{ $helper->id }}">{{ $helper->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-semibold text-gw-primary">
                    Location or meeting link
                    <input type="text" wire:model="locationOrLink" class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="Office, Zoom URL, etc.">
                </label>
                <label class="block text-sm font-semibold text-gw-primary md:col-span-2">
                    Purpose
                    <input type="text" wire:model="purpose" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                </label>
                <label class="block text-sm font-semibold text-gw-primary md:col-span-2">
                    Notes
                    <textarea wire:model="notes" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                </label>
                <div class="md:col-span-2">
                    <button type="submit" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">
                        {{ $editingAppointmentId ? 'Update Appointment' : 'Save Appointment' }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="overflow-hidden rounded-lg border border-gw-accent/30 bg-white shadow-sm">
            <div class="border-b border-gw-accent/20 bg-gw-primary px-4 py-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Upcoming Appointments</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($upcomingAppointments as $appointment)
                    <article wire:key="upcoming-{{ $appointment->id }}" class="p-4 hover:bg-gw-accent-soft/50">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <a href="{{ route('team.prospects.records.show', $appointment->prospect) }}" class="font-semibold text-gw-primary hover:text-gw-accent-dark">
                                    {{ $appointment->prospect->displayName() }}
                                </a>
                                <p class="mt-1 text-sm text-slate-600">{{ $appointment->type?->name ?? 'Appointment' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $appointment->scheduled_at->format('M j, Y g:i A') }}</p>
                                @if ($appointment->location_or_link)
                                    <p class="mt-1 text-xs text-slate-500">{{ $appointment->location_or_link }}</p>
                                @endif
                            </div>
                            <div class="flex flex-col gap-1">
                                <button type="button" wire:click="openEditForm({{ $appointment->id }})" class="rounded border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">Edit</button>
                                <button type="button" wire:click="cancelAppointment({{ $appointment->id }})" class="rounded border border-red-200 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Cancel</button>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="p-6 text-center text-sm text-slate-500">No upcoming appointments scheduled.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Recent History</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentAppointments as $appointment)
                    <article wire:key="recent-{{ $appointment->id }}" class="p-4">
                        <div class="font-semibold text-gw-primary">{{ $appointment->prospect->displayName() }}</div>
                        <p class="mt-1 text-sm text-slate-600">{{ $appointment->type?->name ?? 'Appointment' }} · {{ str($appointment->status)->title() }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $appointment->scheduled_at->format('M j, Y g:i A') }}</p>
                    </article>
                @empty
                    <p class="p-6 text-center text-sm text-slate-500">No recent appointment history.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
