<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request. Creates a new tenant and admin user.
     */
    public function register(RegisterRequest $request)
    {
        $user = DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name' => $request->company_name,
                'slug' => Str::slug($request->company_name) . '-' . Str::random(5),
                'email' => $request->email,
                'status' => 'active',
            ]);

            $adminRole = Role::where('name', 'admin')->first();

        return User::create([
                'tenant_id' => $tenant->id,
                'role_id' => $adminRole->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'onboarding_completed' => false,
            ]);
        });

        Auth::login($user);

        return redirect()->route('onboarding.index');
    }
}
