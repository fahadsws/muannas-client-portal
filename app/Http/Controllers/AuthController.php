<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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

        // WhatsApp review links send people to login first — bring them back to where they were going
        return redirect()->intended($this->home($user));
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

    // ── forgot / reset password ──────────────────────────────

    public function forgotForm()
    {
        return view('auth.forgot');
    }

    public function sendResetLink(Request $r)
    {
        $r->validate(['email' => 'required|email']);
        Password::sendResetLink($r->only('email'));

        // same answer whether or not the email exists, so accounts can't be probed
        return back()->with('status', 'If that email has an account, a reset link is on its way. Check your inbox.');
    }

    public function resetForm(Request $r, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $r->query('email')]);
    }

    public function reset(Request $r)
    {
        $v = $r->validate(['token' => 'required', 'email' => 'required|email', 'password' => 'required|string|min:8|max:60|confirmed']);
        $status = Password::reset($v, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect('/login')->with('status', 'Your password was changed. You can sign in now.')
            : back()->withInput($r->only('email'))->withErrors(['email' => 'This reset link is invalid or has expired. Request a new one.']);
    }

    private function home(User $u): string
    {
        return $u->isSuper() ? '/admin' : '/portal';
    }
}
