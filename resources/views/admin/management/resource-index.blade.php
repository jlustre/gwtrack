@if ($embedded ?? false)
    <x-embedded-layout>
        @include('admin.management.partials.resource-index-content')
    </x-embedded-layout>
@else
    <x-app-layout>
        @include('admin.management.partials.resource-index-content')
    </x-app-layout>
@endif
