<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'email_mode' => 'nullable|in:shared,personal',
            'email_from_name' => 'nullable|string|max:100',
            'email_reply_to' => 'nullable|email|max:255',
            'notification_delivery' => 'nullable|in:instant,daily_digest',
        ];

        if ($request->filled('current_password') || $request->filled('password')) {
            $rules['current_password'] = 'required|current_password';
            $rules['password'] = ['required', 'confirmed', Password::min(8)];
        }

        $validated = $request->validate($rules);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->email_mode = $validated['email_mode'] ?? 'shared';
        $user->email_from_name = $validated['email_from_name'] ?? null;
        $user->email_reply_to = $validated['email_reply_to'] ?? null;
        $user->notification_delivery = $validated['notification_delivery'] ?? 'instant';

        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', __('Profile updated successfully.'));
    }
}
