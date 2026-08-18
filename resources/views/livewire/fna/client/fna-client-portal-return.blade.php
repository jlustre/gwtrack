<div class="mx-auto max-w-md space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-gw-primary">Return to your FNA</h2>
        <p class="mt-2 text-sm text-slate-600">Enter the email, phone, and last four SSN digits you used when you first opened your invite.</p>

        <form wire:submit="login" class="mt-6 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700">Email</label>
                <input type="email" wire:model="accessEmail" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                @error('accessEmail') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700">Phone</label>
                <input type="tel" wire:model="accessPhone" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                @error('accessPhone') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700">Last 4 digits of SSN</label>
                <input type="password" inputmode="numeric" maxlength="4" wire:model="accessSsnLastFour"
                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" autocomplete="off">
                @error('accessSsnLastFour') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="w-full rounded-lg bg-gw-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-gw-primary-hover">Continue to FNA</button>
        </form>
    </div>
</div>
