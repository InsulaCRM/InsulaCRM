<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        $tenant = $user->tenant;
        $step = $this->getCurrentStep($user, $tenant);

        return view('onboarding.index', compact('user', 'tenant', 'step'));
    }

    public function complete(Request $request)
    {
        $user = auth()->user();
        $user->update(['onboarding_completed' => true]);

        return redirect()->route('dashboard')->with('success', 'Welcome to ' . config('app.name') . '!');
    }

    public function skip()
    {
        auth()->user()->update(['onboarding_completed' => true]);

        return redirect()->route('dashboard');
    }

    protected function getCurrentStep($user, $tenant): int
    {
        // Step 1: Company profile set (name != default)
        // Step 2: First team member invited
        // Step 3: First lead created
        // Step 4: Complete

        if (!$tenant->name || $tenant->name === 'My Company') {
            return 1;
        }

        $hasTeam = \App\Models\User::where('tenant_id', $tenant->id)
            ->where('id', '!=', $user->id)
            ->exists();

        if (!$hasTeam) {
            return 2;
        }

        $hasLeads = \App\Models\Lead::where('tenant_id', $tenant->id)->exists();

        if (!$hasLeads) {
            return 3;
        }

        return 4;
    }
}
