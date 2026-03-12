<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Authentication routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// Email verification route (authenticated but not fully verified)
Route::middleware('auth')->group(function () {
    Route::get('/email/verification', [AuthController::class, 'showEmailVerification'])->name('email.verification');
});

// Logout route (authenticated only)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Dashboard route (protected)
Route::get('/dashboard', function () {
    $user = auth()->user();
    
    // If user hasn't verified email, redirect to email verification
    if (!$user->email_verified_at) {
        return redirect()->route('email.verification')->with('info', 'Please verify your email address to continue.');
    }
    
    // If email verified but not approved, auto-approve
    if ($user->email_verified_at && !$user->isApproved()) {
        $user->update(['approved' => true]);
    }
    
    return view('dashboard');
})->name('dashboard')->middleware('auth');

// Redirect root to dashboard or login
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        
        // If user hasn't verified email, redirect to email verification
        if (!$user->email_verified_at) {
            return redirect()->route('email.verification')->with('info', 'Please verify your email address to continue.');
        }
        
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});
