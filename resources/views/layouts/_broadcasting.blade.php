{{--
    Real-Time Broadcasting (Progressive Enhancement)
    Only initializes if a real broadcast driver is configured (reverb or pusher).
    Falls back to the existing 60s polling silently if broadcasting is unavailable.
--}}
@php
    $broadcastDriver = config('broadcasting.default');
    $broadcastEnabled = $broadcastDriver && ! in_array($broadcastDriver, ['null', 'log']);
@endphp

@if($broadcastEnabled && auth()->check())
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
@if($broadcastDriver === 'reverb')
<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        var echoInstance = new Echo({
            broadcaster: 'reverb',
            key: '{{ config("broadcasting.connections.reverb.key") }}',
            wsHost: '{{ config("broadcasting.connections.reverb.options.host", "127.0.0.1") }}',
            wsPort: {{ (int) config("broadcasting.connections.reverb.options.port", 8080) }},
            wssPort: {{ (int) config("broadcasting.connections.reverb.options.port", 8080) }},
            forceTLS: {{ config("broadcasting.connections.reverb.options.useTLS") ? 'true' : 'false' }},
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '{{ url("/broadcasting/auth") }}',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            }
        });

        initBroadcastListeners(echoInstance);
    } catch (e) {
        console.log('InsulaCRM: Broadcasting unavailable, using polling fallback.');
    }
});
</script>
@elseif($broadcastDriver === 'pusher')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        var echoInstance = new Echo({
            broadcaster: 'pusher',
            key: '{{ config("broadcasting.connections.pusher.key") }}',
            cluster: '{{ config("broadcasting.connections.pusher.options.cluster", "mt1") }}',
            forceTLS: true,
            authEndpoint: '{{ url("/broadcasting/auth") }}',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            }
        });

        initBroadcastListeners(echoInstance);
    } catch (e) {
        console.log('InsulaCRM: Broadcasting unavailable, using polling fallback.');
    }
});
</script>
@endif

<script>
function initBroadcastListeners(echo) {
    var tenantId = {{ auth()->user()->tenant_id ?? 0 }};
    var userId = {{ auth()->id() ?? 0 }};

    if (!tenantId || !userId) return;

    // Disable polling if Echo connects successfully
    var pollingDisabled = false;

    // Listen on tenant channel for lead and deal events
    echo.private('tenant.' + tenantId)
        .listen('.lead.created', function(data) {
            pollingDisabled = true;

            // Update lead count badge if present in sidebar/header
            var leadCountBadge = document.querySelector('[data-lead-count]');
            if (leadCountBadge) {
                var current = parseInt(leadCountBadge.textContent) || 0;
                leadCountBadge.textContent = current + 1;
            }

            // If on leads index page, show a toast/banner for new lead
            if (window.location.pathname.indexOf('/leads') !== -1 && window.location.pathname.indexOf('/leads/') === -1) {
                showBroadcastToast('New lead: ' + data.full_name, 'info');
            }
        })
        .listen('.deal.updated', function(data) {
            pollingDisabled = true;

            // If on pipeline page, show a toast for deal updates
            if (window.location.pathname.indexOf('/pipeline') !== -1) {
                showBroadcastToast('Deal updated: ' + (data.title || 'Deal #' + data.id), 'info');
            }
        });

    // Listen on user channel for personal notifications
    echo.private('user.' + userId)
        .listen('.notification.created', function(data) {
            pollingDisabled = true;

            // Update notification bell count
            var bellBadge = document.querySelector('.notification-count');
            if (bellBadge) {
                var currentCount = parseInt(bellBadge.textContent) || 0;
                bellBadge.textContent = currentCount + 1;
                bellBadge.style.display = '';
                bellBadge.classList.remove('d-none');
            }

            // Add to notification dropdown if visible
            var dropdown = document.querySelector('.notification-dropdown-list');
            if (dropdown && data.data && data.data.message) {
                var item = document.createElement('a');
                item.href = data.data.url || '#';
                item.className = 'list-group-item list-group-item-action py-2 px-3 bg-azure-lt';
                item.innerHTML = '<div class="small fw-semibold">' + escapeHtml(data.data.message) + '</div>' +
                                 '<div class="text-secondary" style="font-size:0.75rem;">Just now</div>';
                dropdown.prepend(item);
            }
        });

    // Suppress the default 60s AJAX poll if Echo is connected
    echo.connector.pusher.connection.bind('connected', function() {
        pollingDisabled = true;
        // Override the global notification polling interval if it exists
        if (window._notificationPollInterval) {
            clearInterval(window._notificationPollInterval);
        }
    });

    echo.connector.pusher.connection.bind('disconnected', function() {
        // Re-enable polling on disconnect for graceful degradation
        pollingDisabled = false;
    });
}

function showBroadcastToast(message, type) {
    // Create a simple toast notification at top-right
    var container = document.getElementById('broadcast-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'broadcast-toast-container';
        container.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;max-width:350px;';
        document.body.appendChild(container);
    }

    var toast = document.createElement('div');
    toast.className = 'alert alert-' + (type || 'info') + ' alert-dismissible shadow-sm mb-2';
    toast.setAttribute('role', 'alert');
    toast.style.cssText = 'animation:fadeInRight 0.3s ease;font-size:0.875rem;';
    toast.innerHTML = escapeHtml(message) +
        '<button type="button" class="btn-close btn-close-sm" style="font-size:0.65rem;padding:0.75rem;" onclick="this.parentElement.remove()"></button>';
    container.appendChild(toast);

    // Auto-dismiss after 6 seconds
    setTimeout(function() {
        if (toast.parentElement) {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(function() { toast.remove(); }, 300);
        }
    }, 6000);
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
<style>
@keyframes fadeInRight {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}
</style>
@endif
