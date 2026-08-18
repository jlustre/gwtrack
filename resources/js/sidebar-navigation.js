const OPEN_GROUPS_KEY = 'gwtrack.sidebar.openGroups';

const TOP_ACTIVE = ['bg-gw-accent', 'text-gw-primary'];
const TOP_INACTIVE = ['text-slate-200', 'hover:bg-white/10', 'hover:text-white'];
const CHILD_ACTIVE = ['bg-white', 'text-gw-primary'];
const CHILD_INACTIVE = ['text-slate-300', 'hover:bg-white/10', 'hover:text-white'];

let documentListenersBound = false;

function getNav() {
    return document.getElementById('gw-sidebar-navigation');
}

function readSavedOpenGroups() {
    try {
        const raw = localStorage.getItem(OPEN_GROUPS_KEY);

        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function persistOpenGroups(nav) {
    const open = [...nav.querySelectorAll('[data-sidebar-group-button]')]
        .filter((button) => button.dataset.open === 'true')
        .map((button) => button.dataset.sidebarGroup);

    localStorage.setItem(OPEN_GROUPS_KEY, JSON.stringify(open));
}

function setGroupOpen(nav, group, open) {
    const button = nav.querySelector(`[data-sidebar-group-button][data-sidebar-group="${group}"]`);
    const panel = nav.querySelector(`[data-sidebar-group-panel][data-sidebar-group="${group}"]`);

    if (! button || ! panel) {
        return;
    }

    button.dataset.open = open ? 'true' : 'false';
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
    panel.hidden = ! open;
}

function closeOtherGroups(nav, exceptGroup = '') {
    nav.querySelectorAll('[data-sidebar-group-button]').forEach((button) => {
        const group = button.dataset.sidebarGroup || '';

        if (group && group !== exceptGroup) {
            setGroupOpen(nav, group, false);
        }
    });
}

function openGroupExclusive(nav, group) {
    if (! group) {
        return;
    }

    closeOtherGroups(nav, group);
    setGroupOpen(nav, group, true);
}

function toggleGroup(nav, group) {
    const button = nav.querySelector(`[data-sidebar-group-button][data-sidebar-group="${group}"]`);

    if (! button) {
        return;
    }

    const willOpen = button.dataset.open !== 'true';

    if (willOpen) {
        openGroupExclusive(nav, group);
    } else {
        setGroupOpen(nav, group, false);
    }

    persistOpenGroups(nav);
}

function linkStyleGroups(link) {
    return link.dataset.sidebarGroup ? { active: CHILD_ACTIVE, inactive: CHILD_INACTIVE } : { active: TOP_ACTIVE, inactive: TOP_INACTIVE };
}

function applyLinkActiveState(link, active) {
    const styles = linkStyleGroups(link);

    link.classList.remove(...styles.active, ...styles.inactive);
    link.classList.add(...(active ? styles.active : styles.inactive));
    link.toggleAttribute('data-sidebar-active', active);
}

function syncActiveLinkFromUrl(nav) {
    const current = new URL(window.location.href);
    let matched = null;

    nav.querySelectorAll('[data-sidebar-link]').forEach((link) => {
        let active = false;

        try {
            const target = new URL(link.href, window.location.href);

            if (target.origin !== current.origin) {
                active = false;
            } else if (link.dataset.activeStrict === 'true') {
                active = target.pathname.replace(/\/$/, '') === current.pathname.replace(/\/$/, '')
                    && target.search === current.search;
            } else {
                const targetPath = target.pathname.replace(/\/$/, '') || '/';
                const currentPath = current.pathname.replace(/\/$/, '') || '/';

                active = currentPath === targetPath || currentPath.startsWith(`${targetPath}/`);
            }
        } catch {
            active = false;
        }

        applyLinkActiveState(link, active);

        if (active) {
            matched = link;
        }
    });

    if (matched?.dataset.sidebarGroup) {
        openGroupExclusive(nav, matched.dataset.sidebarGroup);
    }

    return matched;
}

function syncOpenGroups(nav) {
    const serverActiveGroup = nav.dataset.serverActiveGroup || '';
    const savedOpenGroups = readSavedOpenGroups();
    const defaultOpen = [...nav.querySelectorAll('[data-sidebar-group-button][data-open="true"]')]
        .map((button) => button.dataset.sidebarGroup);
    let openGroups = savedOpenGroups ?? defaultOpen;

    if (serverActiveGroup && ! openGroups.includes(serverActiveGroup)) {
        openGroups = [...openGroups, serverActiveGroup];
    }

    // Accordion: keep only one group open (prefer the route's active group).
    let activeGroup = '';

    if (serverActiveGroup && openGroups.includes(serverActiveGroup)) {
        activeGroup = serverActiveGroup;
    } else if (openGroups.length) {
        activeGroup = openGroups[openGroups.length - 1];
    }

    nav.querySelectorAll('[data-sidebar-group-button]').forEach((button) => {
        setGroupOpen(nav, button.dataset.sidebarGroup, button.dataset.sidebarGroup === activeGroup);
    });

    persistOpenGroups(nav);
}

function bindDocumentListeners() {
    if (documentListenersBound) {
        return;
    }

    documentListenersBound = true;

    document.addEventListener('click', (event) => {
        const nav = getNav();

        if (! nav) {
            return;
        }

        const button = event.target.closest('[data-sidebar-group-button]');

        if (button && nav.contains(button)) {
            event.preventDefault();
            toggleGroup(nav, button.dataset.sidebarGroup || '');

            return;
        }

        const link = event.target.closest('[data-sidebar-link]');

        if (! link || ! nav.contains(link)) {
            return;
        }

        nav.querySelectorAll('[data-sidebar-link]').forEach((entry) => applyLinkActiveState(entry, entry === link));

        const group = link.dataset.sidebarGroup || '';

        if (group) {
            openGroupExclusive(nav, group);
            persistOpenGroups(nav);
        }
    });
}

export function initSidebarNavigation() {
    bindDocumentListeners();

    const nav = getNav();

    if (! nav) {
        return;
    }

    syncOpenGroups(nav);
    syncActiveLinkFromUrl(nav);
}

export function refreshSidebarNavigation() {
    initSidebarNavigation();
}
