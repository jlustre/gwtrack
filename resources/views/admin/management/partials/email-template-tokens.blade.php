@if (! empty($config['token_reference']))
    <div class="rounded-lg border border-slate-200 bg-slate-50 p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gw-primary">Available merge tokens</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Insert tokens in the subject or body using
                    <code class="rounded bg-white px-1 py-0.5 text-xs text-gw-primary">&#123;&#123; token_name &#125;&#125;</code>
                    (spaces optional). They are replaced when the email is sent. The body supports HTML formatting.
                </p>
            </div>
            @if (auth()->user()?->hasAnyRole(['super-admin', 'admin']))
                <a
                    href="{{ route('admin.management.resource.index', 'email-template-tokens') }}"
                    class="inline-flex items-center rounded-md border border-gw-accent bg-white px-3 py-1.5 text-xs font-semibold text-gw-primary transition hover:bg-gw-accent/10"
                >
                    Manage tokens
                </a>
            @endif
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @foreach ($config['token_reference'] as $token)
                @php
                    $tokenKey = is_object($token) ? $token->key : $token;
                    $tokenName = is_object($token) ? ($token->name ?? $tokenKey) : $tokenKey;
                    $tokenDescription = is_object($token) ? ($token->description ?? null) : null;
                    $tokenSample = is_object($token) ? ($token->sample_value ?? null) : null;
                @endphp
                <div class="rounded-md border border-slate-200 bg-white p-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <code class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-gw-primary">&#123;&#123; {{ $tokenKey }} &#125;&#125;</code>
                        <span class="text-xs font-medium text-slate-500">{{ $tokenName }}</span>
                    </div>
                    @if ($tokenDescription)
                        <p class="mt-2 text-xs leading-5 text-slate-600">{{ $tokenDescription }}</p>
                    @endif
                    @if ($tokenSample)
                        <p class="mt-2 text-xs text-slate-500">
                            <span class="font-semibold uppercase tracking-wide text-slate-400">Example</span>
                            {{ $tokenSample }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
