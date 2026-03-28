/**
 * InsulaCRM Service Worker
 * Provides offline support and caching for the PWA experience.
 *
 * Cache strategies:
 *   - App shell (CSS, JS, fonts): cache-first
 *   - API/AJAX calls: network-first with timeout
 *   - HTML pages: network-first, offline fallback
 *   - Static assets (images): cache-first
 *
 * Bump CACHE_VERSION to invalidate all caches on deploy.
 */

var CACHE_VERSION = 'v1.0.0';
var STATIC_CACHE = 'insulacrm-static-' + CACHE_VERSION;
var DYNAMIC_CACHE = 'insulacrm-dynamic-' + CACHE_VERSION;

// Derive base path from service worker location (supports subdirectory installs)
var BASE_PATH = self.location.pathname.replace(/\/service-worker\.js$/, '') + '/';

// App shell resources to cache on install
var APP_SHELL = [
    BASE_PATH + 'offline',
    'https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css',
    'https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler-vendors.min.css',
    'https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js',
];

// Patterns for static assets (cache-first)
var STATIC_PATTERNS = [
    /\.(?:css|js|woff2?|ttf|eot|otf)(\?.*)?$/,
    /\/img\//,
    /\/images\//,
    /\/fonts\//,
    /cdn\.jsdelivr\.net/,
    /cdnjs\.cloudflare\.com/,
];

// Patterns for API/AJAX calls (network-first)
var API_PATTERNS = [
    /\/api\//,
    /\/ai\//,
    /\/search/,
    /\/notifications\/recent/,
    /\/calendar\/events/,
    /\/dashboard-data/,
];

/**
 * Install event: cache the app shell.
 */
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(STATIC_CACHE).then(function(cache) {
            return cache.addAll(APP_SHELL).catch(function(error) {
                // Non-critical: some CDN resources may fail on first install
                console.log('Service worker: some app shell resources failed to cache', error);
            });
        }).then(function() {
            return self.skipWaiting();
        })
    );
});

/**
 * Activate event: clean up old caches.
 */
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.filter(function(name) {
                    return name.startsWith('insulacrm-') && name !== STATIC_CACHE && name !== DYNAMIC_CACHE;
                }).map(function(name) {
                    return caches.delete(name);
                })
            );
        }).then(function() {
            return self.clients.claim();
        })
    );
});

/**
 * Fetch event: apply appropriate caching strategy.
 */
self.addEventListener('fetch', function(event) {
    var request = event.request;

    // Only handle GET requests
    if (request.method !== 'GET') return;

    // Skip chrome-extension and non-http(s) requests
    if (!request.url.startsWith('http')) return;

    // Determine strategy based on URL patterns
    if (isStaticAsset(request.url)) {
        // Cache-first for static assets
        event.respondWith(cacheFirst(request));
    } else if (isApiRequest(request.url)) {
        // Network-first for API calls (no offline fallback for JSON)
        event.respondWith(networkFirst(request));
    } else if (request.headers.get('accept') && request.headers.get('accept').includes('text/html')) {
        // Network-first for HTML pages with offline fallback
        event.respondWith(networkFirstWithFallback(request));
    }
});

/**
 * Cache-first strategy: try cache, fall back to network.
 */
function cacheFirst(request) {
    return caches.match(request).then(function(cached) {
        if (cached) return cached;

        return fetch(request).then(function(response) {
            if (response && response.status === 200) {
                var responseClone = response.clone();
                caches.open(STATIC_CACHE).then(function(cache) {
                    cache.put(request, responseClone);
                });
            }
            return response;
        }).catch(function() {
            // Static asset unavailable offline - return nothing
            return new Response('', { status: 408, statusText: 'Offline' });
        });
    });
}

/**
 * Network-first strategy: try network, fall back to cache.
 */
function networkFirst(request) {
    return fetchWithTimeout(request, 5000).then(function(response) {
        if (response && response.status === 200) {
            var responseClone = response.clone();
            caches.open(DYNAMIC_CACHE).then(function(cache) {
                cache.put(request, responseClone);
            });
        }
        return response;
    }).catch(function() {
        return caches.match(request);
    });
}

/**
 * Network-first with offline page fallback (for HTML navigation).
 */
function networkFirstWithFallback(request) {
    return fetchWithTimeout(request, 8000).then(function(response) {
        if (response && response.status === 200) {
            var responseClone = response.clone();
            caches.open(DYNAMIC_CACHE).then(function(cache) {
                cache.put(request, responseClone);
            });
        }
        return response;
    }).catch(function() {
        return caches.match(request).then(function(cached) {
            if (cached) return cached;
            // Show offline fallback page
            return caches.match(BASE_PATH + 'offline');
        });
    });
}

/**
 * Fetch with a timeout to avoid hanging on slow connections.
 */
function fetchWithTimeout(request, timeout) {
    return new Promise(function(resolve, reject) {
        var timer = setTimeout(function() {
            reject(new Error('Request timeout'));
        }, timeout);

        fetch(request).then(function(response) {
            clearTimeout(timer);
            resolve(response);
        }).catch(function(error) {
            clearTimeout(timer);
            reject(error);
        });
    });
}

/**
 * Check if a URL matches static asset patterns.
 */
function isStaticAsset(url) {
    return STATIC_PATTERNS.some(function(pattern) {
        return pattern.test(url);
    });
}

/**
 * Check if a URL matches API request patterns.
 */
function isApiRequest(url) {
    return API_PATTERNS.some(function(pattern) {
        return pattern.test(url);
    });
}
