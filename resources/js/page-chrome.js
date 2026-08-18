let initialized = false;
let spaNavigationInFlight = false;

const GUEST_PATH_PREFIXES = [
    '/login',
    '/register',
    '/forgot-password',
    '/reset-password',
    '/confirm-password',
    '/verify-email',
    '/fna/client',
];

function getOverlay() {
    return document.getElementById('gw-page-loading');
}

function showLoading() {
    if (spaNavigationInFlight) {
        return;
    }

    const overlay = getOverlay();

    if (! overlay) {
        return;
    }

    overlay.classList.remove('hidden');
    overlay.style.removeProperty('display');
    overlay.setAttribute('aria-hidden', 'false');
}

function hideLoading() {
    const overlay = getOverlay();

    if (! overlay) {
        return;
    }

    overlay.classList.add('hidden');
    overlay.style.display = 'none';
    overlay.setAttribute('aria-hidden', 'true');
}

function shouldHandleLink(anchor) {
    if (! anchor || anchor.target === '_blank' || anchor.hasAttribute('download')) {
        return false;
    }

    const href = anchor.getAttribute('href');

    if (! href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) {
        return false;
    }

    try {
        const url = new URL(anchor.href, window.location.href);

        return url.origin === window.location.origin;
    } catch {
        return false;
    }
}

function isGuestPath(pathname) {
    return GUEST_PATH_PREFIXES.some((prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`));
}

function hasWireNavigate(anchor) {
    return Array.from(anchor.attributes).some((attribute) => attribute.name === 'wire:navigate' || attribute.name.startsWith('wire:navigate.'));
}

function canUseLivewireNavigate() {
    return typeof window.Livewire?.navigate === 'function' && document.getElementById('gw-sidebar-navigation');
}

function initGoToTop() {
    const goTop = document.getElementById('gw-go-to-top');

    if (! goTop || goTop.dataset.gwBound === '1') {
        return;
    }

    goTop.dataset.gwBound = '1';

    const scrollPosition = () => Math.max(
        window.scrollY || 0,
        document.documentElement.scrollTop || 0,
        document.body.scrollTop || 0,
    );

    const updateGoTop = () => {
        goTop.classList.toggle('gw-go-to-top--visible', scrollPosition() > 100);
    };

    const scrollToTop = () => {
        const startY = scrollPosition();

        if (startY <= 0) {
            return;
        }

        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (! prefersReduced && 'scrollBehavior' in document.documentElement.style) {
            window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
            document.documentElement.scrollTo({ top: 0, left: 0, behavior: 'smooth' });

            return;
        }

        const duration = prefersReduced ? 0 : 550;
        const startTime = performance.now();

        const step = (now) => {
            const progress = Math.min((now - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const y = Math.round(startY * (1 - eased));

            window.scrollTo(0, y);
            document.documentElement.scrollTop = y;
            document.body.scrollTop = y;

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };

        requestAnimationFrame(step);
    };

    goTop.addEventListener('click', scrollToTop);
    window.addEventListener('scroll', updateGoTop, { passive: true });
    document.addEventListener('scroll', updateGoTop, { passive: true });
    window.addEventListener('resize', updateGoTop, { passive: true });
    window.addEventListener('load', updateGoTop);
    updateGoTop();
}

function bindSpaLinkNavigation() {
    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0) {
            return;
        }

        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const link = event.target.closest('a[href]');

        if (! shouldHandleLink(link) || link.hasAttribute('data-no-navigate')) {
            return;
        }

        // Livewire already handles explicit wire:navigate links.
        if (hasWireNavigate(link)) {
            return;
        }

        if (! canUseLivewireNavigate()) {
            return;
        }

        let url;

        try {
            url = new URL(link.href, window.location.href);
        } catch {
            return;
        }

        // Leaving the authenticated shell (guest/public layouts) should be a full reload.
        if (isGuestPath(url.pathname)) {
            return;
        }

        event.preventDefault();
        spaNavigationInFlight = true;
        hideLoading();
        window.Livewire.navigate(url.pathname + url.search + url.hash);
    });
}

export function initPageChrome() {
    if (initialized) {
        return;
    }

    initialized = true;

    showLoading();

    const hideWhenReady = () => hideLoading();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideWhenReady, { once: true });
    } else {
        hideWhenReady();
    }

    window.addEventListener('load', hideWhenReady, { once: true });
    window.setTimeout(hideWhenReady, 12000);

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            hideLoading();
        }
    });

    document.addEventListener('livewire:navigating', () => {
        spaNavigationInFlight = true;
        hideLoading();
    });

    document.addEventListener('livewire:navigated', () => {
        spaNavigationInFlight = false;
        hideLoading();
        initGoToTop();
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');

        if (! shouldHandleLink(link)) {
            return;
        }

        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        // SPA navigations use Livewire's progress bar instead of the full-page overlay.
        if (hasWireNavigate(link) || (canUseLivewireNavigate() && ! link.hasAttribute('data-no-navigate'))) {
            try {
                const url = new URL(link.href, window.location.href);

                if (! isGuestPath(url.pathname)) {
                    spaNavigationInFlight = true;
                    hideLoading();

                    return;
                }
            } catch {
                // Fall through to full-page loader.
            }
        }

        showLoading();
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (! (form instanceof HTMLFormElement) || form.target === '_blank') {
            return;
        }

        if (event.defaultPrevented || form.hasAttribute('data-no-page-loader')) {
            return;
        }

        // Livewire submits in-place; do not show the full-page loader.
        if (form.closest('[wire\\:id]') || Array.from(form.attributes).some((attribute) => attribute.name.startsWith('wire:'))) {
            return;
        }

        showLoading();
        window.setTimeout(hideLoading, 15000);
    });

    bindSpaLinkNavigation();
    initGoToTop();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPageChrome);
} else {
    initPageChrome();
}
