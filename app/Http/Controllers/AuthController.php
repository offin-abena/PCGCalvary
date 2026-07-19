<?php

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Http\Controllers\Controller;
use App\Core\LoginThrottle;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        \App\Core\View::render('auth/login', ['title' => 'Login', 'layout' => null]);
    }

    public function login(): void
    {
        $username = trim((string) $this->input('username'));

        if (LoginThrottle::tooManyAttempts($username)) {
            $wait = (int) ceil(LoginThrottle::retryAfterSeconds($username) / 60);
            $this->flash('danger', 'Too many failed login attempts. Try again in ' . max(1, $wait) . ' minute(s).');
            $this->redirect('login');
        }

        $errors = $this->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($errors) {
            session(['_old_input' => request()->all()]);
            $this->flash('danger', reset($errors));
            $this->redirect('login');
        }

        if (Auth::attempt($username, $this->input('password'))) {
            LoginThrottle::clear($username);
            session()->forget('_old_input');
            $this->redirect('dashboard');
        }

        LoginThrottle::recordFailure($username);
        $this->flash('danger', 'Invalid username or password.');
        $this->redirect('login');
    }

    public function logout(): void
    {
        if ($this->input('reason') === 'inactivity') {
            Auth::logoutDueToInactivity();
        } else {
            Auth::logout();
        }

        $this->redirect('login');
    }
}
