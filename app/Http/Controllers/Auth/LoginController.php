<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showCitizen(): View
    {
        return view('auth.login', ['portal' => 'citizen']);
    }

    public function showStaff(): View
    {
        return view('auth.login', ['portal' => 'staff']);
    }

    public function loginCitizen(Request $request): RedirectResponse
    {
        return $this->attemptLogin($request, [User::TYPE_CITIZEN], 'citizen.dashboard');
    }

    public function loginStaff(Request $request): RedirectResponse
    {
        return $this->attemptLogin($request, [User::TYPE_SUPER_ADMIN, User::TYPE_ADMIN, User::TYPE_EXECUTIVE], 'admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $wasStaff = $request->user()?->isAdminStaff();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($wasStaff ? 'staff.login' : 'login');
    }

    public function showPatternVerify(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('pending_pattern_user_id')) {
            return redirect()->route('login');
        }

        $user = User::find($request->session()->get('pending_pattern_user_id'));
        if (! $user) {
            $request->session()->forget(['pending_pattern_user_id', 'pending_pattern_remember']);

            return redirect()->route('login');
        }

        return view('auth.pattern-verify', ['user' => $user]);
    }

    public function verifyPattern(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('pending_pattern_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'pattern' => ['required', 'string'],
        ]);

        $user = User::find($userId);
        if (! $user || ! $user->hasPatternLock() || ! \Illuminate\Support\Facades\Hash::check($request->string('pattern')->toString(), $user->pattern_lock_hash)) {
            throw ValidationException::withMessages([
                'pattern' => 'Invalid pattern lock sequence. Please try again.',
            ]);
        }

        $remember = (bool) $request->session()->get('pending_pattern_remember', false);
        $request->session()->forget(['pending_pattern_user_id', 'pending_pattern_remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('citizen.dashboard'));
    }

    /**
     * @param  list<string>  $allowedTypes
     */
    private function attemptLogin(Request $request, array $allowedTypes, string $redirectRoute): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower($credentials['email']);
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! \Illuminate\Support\Facades\Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user->is_active || ! in_array($user->user_type, $allowedTypes, true)) {
            throw ValidationException::withMessages([
                'email' => 'You are not allowed to access this portal.',
            ]);
        }

        if ($user->hasPatternLock()) {
            $request->session()->put('pending_pattern_user_id', $user->id);
            $request->session()->put('pending_pattern_remember', $request->boolean('remember'));

            return redirect()->route('pattern.verify');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route($redirectRoute));
    }
}
