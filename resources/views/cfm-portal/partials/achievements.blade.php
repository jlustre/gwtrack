<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="mb-4 text-lg font-semibold text-gw-primary">Achievements</h3>

    <ul class="space-y-3">
        @foreach ($achievements as $achievement)
            <li class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                <span class="text-2xl leading-none" aria-hidden="true">{{ $achievement['icon'] }}</span>
                <div>
                    <p class="font-medium text-gw-primary">{{ $achievement['title'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $achievement['description'] }}</p>
                </div>
            </li>
        @endforeach
    </ul>
</div>
