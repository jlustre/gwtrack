import Alpine from 'alpinejs';
import './page-chrome';
import { rebuildProvinceSelectOptions } from './location-province-select';
import taskManagement from './task-management';
import cfmManagement from './cfm-management';
import profileTableFilter from './profile-table-filter';
import orgChartBoard from './org-chart-board';
import downlineHierarchyTable from './downline-hierarchy-table';
import profilePhotoUpload from './profile-photo-upload';
import prospectActivitiesModal from './prospect-activities-modal';
import pipelineStageProspectsModal from './pipeline-stage-prospects-modal';
import genealogyTreePan from './genealogy-tree-pan';
import genealogyTreeBranch from './genealogy-tree-branch';
import dashboardStats from './dashboard-stats';
import prospectKanbanBoard from './prospect-kanban-board';
import globalSearch from './global-search';
import notificationPush from './notification-push';
import { initSidebarNavigation, refreshSidebarNavigation } from './sidebar-navigation';

window.rebuildProvinceSelectOptions = rebuildProvinceSelectOptions;
window.prospectActivitiesModal = prospectActivitiesModal;
window.pipelineStageProspectsModal = pipelineStageProspectsModal;

// Register Alpine data on Livewire's Alpine instance (started by @livewireScripts).
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    const media = window.matchMedia('(min-width: 1024px)');

    Alpine.store('shell', {
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('gwtrack.sidebarCollapsed') === '1',
        isDesktop: media.matches,
        updateViewport() {
            this.isDesktop = media.matches;

            if (this.isDesktop) {
                this.sidebarOpen = false;
            }
        },
        toggleSidebar() {
            if (this.isDesktop) {
                this.sidebarCollapsed = ! this.sidebarCollapsed;
                localStorage.setItem('gwtrack.sidebarCollapsed', this.sidebarCollapsed ? '1' : '0');

                return;
            }

            this.sidebarOpen = ! this.sidebarOpen;
        },
        closeMobileSidebar() {
            this.sidebarOpen = false;
        },
    });

    media.addEventListener('change', () => Alpine.store('shell').updateViewport());

    Alpine.data('taskManagement', taskManagement);
    Alpine.data('cfmManagement', cfmManagement);
    Alpine.data('profileTableFilter', profileTableFilter);
    Alpine.data('orgChartBoard', orgChartBoard);
    Alpine.data('downlineHierarchyTable', downlineHierarchyTable);
    Alpine.data('profilePhotoUpload', profilePhotoUpload);
    Alpine.data('prospectActivitiesModal', prospectActivitiesModal);
    Alpine.data('pipelineStageProspectsModal', pipelineStageProspectsModal);
    Alpine.data('genealogyTreePan', genealogyTreePan);
    Alpine.data('genealogyTreeBranch', genealogyTreeBranch);
    Alpine.data('dashboardStats', dashboardStats);
    Alpine.data('prospectKanbanBoard', prospectKanbanBoard);
    Alpine.data('globalSearch', () => globalSearch(window.__gwSearchSuggestUrl ?? '/search/suggest'));
    Alpine.data('notificationPush', notificationPush);
});

document.addEventListener('DOMContentLoaded', () => initSidebarNavigation());
document.addEventListener('livewire:navigated', () => {
    refreshSidebarNavigation();

    try {
        window.Alpine?.store('shell')?.closeMobileSidebar();
    } catch {
        // Alpine may not be ready on guest layouts.
    }
});

// Guest/auth pages load Vite but not @livewireScripts, so Alpine never booted there.
if (document.querySelector('script[src*="livewire"]') === null) {
    window.Alpine = Alpine;
    Alpine.start();
}
