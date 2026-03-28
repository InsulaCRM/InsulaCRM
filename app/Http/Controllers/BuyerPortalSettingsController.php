<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class BuyerPortalSettingsController extends Controller
{
    /**
     * Update portal settings for the tenant.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'buyer_portal_enabled'     => 'nullable|boolean',
            'buyer_portal_headline'    => 'nullable|string|max:255',
            'buyer_portal_description' => 'nullable|string|max:2000',
        ]);

        $tenant = auth()->user()->tenant;

        $tenant->update([
            'buyer_portal_enabled'     => $request->boolean('buyer_portal_enabled'),
            'buyer_portal_headline'    => $validated['buyer_portal_headline'] ?? null,
            'buyer_portal_description' => $validated['buyer_portal_description'] ?? null,
        ]);

        AuditLog::log('settings.buyer_portal.updated', $tenant);

        $label = \App\Services\BusinessModeService::isRealEstate() ? __('Client portal') : __('Buyer portal');

        return redirect()->route('settings.index', ['tab' => 'buyer-portal'])
            ->with('success', $label . ' ' . __('settings updated.'));
    }
}
