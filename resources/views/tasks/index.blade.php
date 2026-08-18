<x-app-layout>
    <livewire:tasks.tasks-page
        :q="$q ?? ''"
        :status="$status ?? ''"
        :priority="$priority ?? ''"
        :assignee="$assignee ?? ''"
        :category="$category ?? ''"
        :module="$module ?? ''"
        :due-date="$dueDate ?? ''"
        :page="$page ?? 1"
        :view="$view ?? 'list'"
    />
</x-app-layout>
