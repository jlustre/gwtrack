@props([
    'title',
    'subtitle' => null,
])

<x-admin.page-hero
    eyebrow="Phase 0 Scaffold"
    :title="$title"
    :description="$subtitle"
/>
