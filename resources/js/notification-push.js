export default function notificationPush(config = {}) {
    const routes = {
        vapid: config.vapidUrl || '/notifications/push/vapid-public-key',
        store: config.storeUrl || '/notifications/device-tokens',
        destroy: config.destroyUrl || '/notifications/device-tokens',
    };

    return {
        status: 'idle',
        message: null,
        registeredToken: null,
        browserSupported: typeof window !== 'undefined'
            && 'serviceWorker' in navigator
            && 'PushManager' in window
            && 'Notification' in window,

        async enablePush() {
            if (! this.browserSupported) {
                this.status = 'unsupported';
                this.message = 'This browser does not support push notifications.';

                return;
            }

            this.status = 'working';
            this.message = null;

            try {
                const permission = await Notification.requestPermission();

                if (permission !== 'granted') {
                    this.status = 'denied';
                    this.message = 'Notification permission was not granted.';

                    return;
                }

                const vapidResponse = await fetch(routes.vapid, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                if (! vapidResponse.ok) {
                    throw new Error('Could not load push configuration.');
                }

                const vapid = await vapidResponse.json();

                if (! vapid.enabled) {
                    this.status = 'disabled';
                    this.message = 'Push notifications are not enabled on this portal yet.';

                    return;
                }

                const registration = await navigator.serviceWorker.register('/sw.js');
                await navigator.serviceWorker.ready;

                let subscription = await registration.pushManager.getSubscription();

                if (! subscription && vapid.public_key) {
                    subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(vapid.public_key),
                    });
                }

                if (! subscription) {
                    throw new Error('Could not create a browser push subscription.');
                }

                const subscriptionJson = JSON.stringify(subscription.toJSON());
                const endpointToken = subscription.endpoint.split('/').pop() || subscription.endpoint;

                const storeResponse = await fetch(routes.store, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        token: endpointToken,
                        platform: 'web',
                        device_name: navigator.userAgent.includes('Chrome') ? 'Chrome' : 'Browser',
                        subscription_payload: subscriptionJson,
                    }),
                });

                if (! storeResponse.ok) {
                    throw new Error('Could not save this device for push delivery.');
                }

                const payload = await storeResponse.json();
                this.registeredToken = payload.token;
                this.status = 'enabled';
                this.message = 'Push notifications are enabled for this browser.';
            } catch (error) {
                this.status = 'error';
                this.message = error?.message || 'Could not enable push notifications.';
            }
        },

        async disablePush(token) {
            if (! token) {
                return;
            }

            this.status = 'working';

            try {
                const registration = await navigator.serviceWorker.getRegistration('/sw.js');
                const subscription = await registration?.pushManager.getSubscription();

                if (subscription) {
                    await subscription.unsubscribe();
                }

                await fetch(routes.destroy, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ token }),
                });

                this.registeredToken = null;
                this.status = 'disabled';
                this.message = 'Push notifications were disabled for this browser.';
            } catch (error) {
                this.status = 'error';
                this.message = error?.message || 'Could not disable push notifications.';
            }
        },
    };
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}
