<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset OTP request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset OTP request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->string('email')->lower()->toString();
        $user = User::where('email', $email)->first();

        if ($user) {
            $otp = (string) random_int(100000, 999999);
            $expiresIn = config('auth.passwords.users.expire', 60);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($otp),
                    'created_at' => now(),
                ]
            );

            Mail::to($user->email)->send(new PasswordResetOtp($otp, $expiresIn));
        }

        return redirect()
            ->route('password.reset')
            ->withInput(['email' => $email])
            ->with('status', 'If that email exists in our records, we sent a password reset OTP.');
    }
}
