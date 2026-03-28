<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function toggle(Request $request)
    {
        $user = auth()->user();
        $newTheme = $user->theme === 'dark' ? 'light' : 'dark';
        $user->update(['theme' => $newTheme]);

        if ($request->wantsJson()) {
            return response()->json(['theme' => $newTheme]);
        }

        return back();
    }
}
