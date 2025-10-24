<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Show login page
    public function showLogin()
    {
        // If already logged in, redirect to sales
        if (Auth::check()) {
            return redirect()->route('sales.create');
        }
        return view('pages.auth.login');
    }

    // Process login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            
            // Redirect to intended page or sales.create
            return redirect()->intended(route('sales.create'))
                ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials. Please check your email and password.'
        ])->withInput($request->only('email'));
    }

    // Show signup page (only if no user exists)
    public function showSignup()
    {
        // If user already logged in, redirect
        if (Auth::check()) {
            return redirect()->route('login')
                ->with('info', 'You are already logged in.');
        }

        // If admin already exists, redirect to login
        // if (User::count() > 0) {
        //     return redirect()->route('login')
        //         ->with('info', 'Admin account already exists. Please login.');
        // }
        
        return view('pages.auth.signup');
    }

    // Process signup (creates first admin)
    public function signup(Request $request)
    {
        // Check if admin already exists
        // if (User::count() > 0) {
        //     return redirect()->route('login')
        //         ->with('info', 'Admin account already created. Please login.');
        // }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Do NOT auto-login
return redirect()->route('login')
->with('success', 'Account created successfully! Please login to continue.');
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }

    // Show password reset request form
    public function showReset() 
    {
        // If already logged in, redirect
        if (Auth::check()) {
            return redirect()->route('sales.create');
        }
        
        return view('pages.auth.password_reset');
    }
    
    // Send reset link
    public function sendResetLink(Request $request) 
    {
        $request->validate(['email' => 'required|email']);
        
        $status = Password::sendResetLink($request->only('email'));
    
        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'Password reset link sent to your email!')
            : back()->withErrors(['email' => 'Unable to send reset link. Please check your email.']);
    }

    // Show new password form
    public function showNewPasswordForm(Request $request, $token = null)
    {
        // If already logged in, redirect
        if (Auth::check()) {
            return redirect()->route('sales.create');
        }
        
        return view('pages.auth.password_reset_confirm', [
            'token' => $token, 
            'email' => $request->email
        ]);
    }

    // Reset password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password reset successfully! Please login.')
            : back()->withErrors(['email' => 'Failed to reset password. Please try again.']);
    }
}