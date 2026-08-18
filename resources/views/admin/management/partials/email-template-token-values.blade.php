@php
    $tokenDefinitions = collect($config['token_reference'] ?? []);
    $storedValues = [];

    if (is_array(old('token_values'))) {
        $storedValues = old('token_values');
    } elseif ($record && filled(data_get($record, 'token_values'))) {
        $decoded = data_get($record, 'token_values');
        $storedValues = is_string($decoded)
            ? (json_decode($decoded, true, 512, JSON_THROW_ON_ERROR) ?: [])
            : (array) $decoded;
    }
@endphp

@if ($tokenDefinitions->isNotEmpty())
    <div class="rounded-lg border border-gw-accent/30 bg-gw-accent-soft/40 p-5">
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-gw-primary">Token values for this template</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Set the value each merge token should use when this template is sent. Leave a field blank to use the runtime value from the application (for example, the member's actual name).
                Values you enter here override automatic defaults for this template only.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($tokenDefinitions as $token)
                @php
                    $tokenKey = is_object($token) ? $token->key : $token;
                    $tokenName = is_object($token) ? ($token->name ?? $tokenKey) : $tokenKey;
                    $tokenDescription = is_object($token) ? ($token->description ?? null) : null;
                    $tokenSample = is_object($token) ? ($token->sample_value ?? null) : null;
                    $fieldId = 'token_value_'.$tokenKey;
                    $currentValue = $storedValues[$tokenKey] ?? '';
                @endphp
                <div>
                    <label for="{{ $fieldId }}" class="block text-sm font-semibold text-gw-primary">
                        <code class="rounded bg-white px-1.5 py-0.5 text-xs font-medium text-gw-accent-dark">&#123;&#123; {{ $tokenKey }} &#125;&#125;</code>
                        <span class="ml-1 font-normal text-slate-600">{{ $tokenName }}</span>
                    </label>
                    @if ($tokenDescription)
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ $tokenDescription }}</p>
                    @endif
                    <input
                        id="{{ $fieldId }}"
                        type="text"
                        name="token_values[{{ $tokenKey }}]"
                        value="{{ $currentValue }}"
                        @if ($tokenSample) placeholder="{{ $tokenSample }}" @endif
                        class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                    >
                </div>
            @endforeach
        </div>

        @if ($errors->has('token_values') || $errors->has('token_values.*'))
            <ul class="mt-4 list-inside list-disc text-sm text-red-700">
                @foreach ($errors->get('token_values') as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @foreach ($errors->get('token_values.*') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
