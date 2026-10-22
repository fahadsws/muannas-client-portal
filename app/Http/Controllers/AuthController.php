<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function form()
    {
        return Auth::check() ? redirect($this->home(Auth::user())) : view('login');
    }

    public function login(Request $r)
    {
        $cred = $r->validate(['email' => 'required|email', 'password' => 'required']);
        if (! Auth::attempt($cred, $r->boolean('remember'))) {
            return back()->withErrors(['login' => 'Incorrect email or password.'])->withInput($r->only('email'));
        }
        $user = Auth::user();
        if ($user->agency_id && $user->agency->status !== 'active') {
            Auth::logout();

            return back()->withErrors(['login' => 'This agency account is suspended. Contact the platform admin.'])->withInput($r->only('email'));
        }
        $r->session()->regenerate();

        return redirect($this->home($user));
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/login');
    }

    public function stopImpersonating(Request $r)
    {
        $id = $r->session()->pull('impersonator');
        if ($id) {
            Auth::loginUsingId($id);
        }

        return redirect('/admin');
    }

    private function home(User $u): string
    {
        return $u->isSuper() ? '/admin' : '/board';
    }
}
