@include('resources.partials.portal-resource-favorite-button', [
    'formAction' => route('admin.management.resources.favorite', $recordId),
    'queryParams' => [
        'search' => $filters['search'] ?? '',
        'trashed' => $filters['trashed'] ?? '',
        'category' => $filters['category'] ?? '',
        'embedded' => ($embedded ?? false) ? '1' : '',
    ],
    'isFavorited' => $isFavorited,
])
