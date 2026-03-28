<?php

namespace HelloWorld;

use App\Plugins\BasePlugin;
use Illuminate\Support\Facades\Log;

class HelloWorldPlugin extends BasePlugin
{
    /**
     * Register plugin hooks, menu items, and services.
     */
    public function register(): void
    {
        // Add a menu item to the CRM sidebar
        $this->addMenuItem('Hello World', '/plugin/hello-world', 'fas fa-globe');
    }

    /**
     * Boot the plugin - set up event listeners and routes.
     */
    public function boot(): void
    {
        // Hook into the lead.created action
        $this->hooks->addAction('lead.created', function ($lead) {
            Log::info('HelloWorldPlugin: A new lead was created!', [
                'lead_id' => $lead->id ?? null,
                'name' => ($lead->first_name ?? '') . ' ' . ($lead->last_name ?? ''),
            ]);
        });
    }
}
