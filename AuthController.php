<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Jika intended url mengarah ke endpoint AJAX/polling (logs, progress, status, proses), abaikan & arahkan ke dashboard
            $intended = $request->session()->get('url.intended', '');
            if (str_contains($intended, '/logs') || str_contains($intended, '/progress') || str_contains($intended, '/status') || str_contains($intended, '/proses')) {
                $request->session()->forget('url.intended');
                return redirect()->route('dashboard.mbg');
            }

            // Redirect ke dashboard MBG setelah login
            return redirect()->intended(route('dashboard.mbg'));
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        // JANGAN auto-login, redirect ke halaman login dengan pesan sukses
        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login dengan akun baru Anda.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/about'); // Redirect ke halaman about setelah logout
    }
}