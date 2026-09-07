<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = $request->string('email')->lower()->toString();
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();
        $expiresAt = $record
            ? Carbon::parse($record->created_at)->addMinutes(config('auth.passwords.users.expire', 60))
            : null;

        if (! $record || now()->greaterThan($expiresAt) || ! Hash::check($request->otp, $record->token)) {
            throw ValidationException::withMessages([
                'otp' => __('The OTP is invalid or has expired.'),
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => __('We can\'t find a user with that email address.'),
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', __('Your password has been reset.'));
    }
}
