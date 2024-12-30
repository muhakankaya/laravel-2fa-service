<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorAuthService
{
    /**
     * Generate a 2FA code for the user.
     *
     * @param $user
     * @return string
     */
    public function generateCode($user): string
    {
        $code = random_int(100000, 999999); // Generate a numeric 6-digit code
        $hashedCode = Hash::make($code);

        Cache::put($this->getCacheKey($user->id), $hashedCode, now()->addMinutes(config('2fa.code_expiry', 10)));

        return $code;
    }

    /**
     * Send the 2FA code to the user via email.
     *
     * @param $user
     * @return void
     */
    public function sendCode($user): void
    {
        $code = $this->generateCode($user);

        try {
            Mail::to($user->email)->send(new \App\Mail\TwoFactorCodeMail($code));
        } catch (\Exception $e) {
            \Log::error('Failed to send 2FA email: ' . $e->getMessage());
        }
    }

    /**
     * Validate the provided 2FA code.
     *
     * @param $user
     * @param $code
     * @return bool
     */
    public function validateCode($user, $code): bool
    {
        $hashedCode = Cache::get($this->getCacheKey($user->id));

        if (!$hashedCode) {
            return false; // Code expired
        }

        if (Hash::check($code, $hashedCode)) {
            Cache::forget($this->getCacheKey($user->id));
            return true;
        }

        return false; // Invalid code
    }

    /**
     * Get the cache key for storing the 2FA code.
     *
     * @param int $userId
     * @return string
     */
    private function getCacheKey(int $userId): string
    {
        return '2fa_code_' . $userId;
    }
}

// Example usage within a controller
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TwoFactorAuthService;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorAuthController extends Controller
{
    private TwoFactorAuthService $twoFactorAuthService;

    public function __construct(TwoFactorAuthService $twoFactorAuthService)
    {
        $this->twoFactorAuthService = $twoFactorAuthService;
    }

    public function sendCode(Request $request)
    {
        $user = Auth::user();

        if (RateLimiter::tooManyAttempts('send-2fa-code-' . $user->id, 5)) {
            return response()->json(['message' => 'Too many attempts. Please try again later.'], 429);
        }

        RateLimiter::hit('send-2fa-code-' . $user->id, 60);

        $this->twoFactorAuthService->sendCode($user);

        return response()->json(['message' => '2FA code sent successfully.']);
    }

    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();

        if ($this->twoFactorAuthService->validateCode($user, $request->code)) {
            session(['2fa_verified' => true]);
            return response()->json(['message' => 'Code validated successfully.']);
        }

        return response()->json(['message' => 'Invalid or expired 2FA code.'], 400);
    }
}

// Mailable for sending the code
namespace App\Mail;

use Illuminate\Mail\Mailable;

class TwoFactorCodeMail extends Mailable
{
    public string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function build()
    {
        return $this->subject('Your Two-Factor Authentication Code')->view('emails.two_factor_code');
    }
}

// Blade view (resources/views/emails/two_factor_code.blade.php)
<p>Your 2FA Code is: <strong>{{ $code }}</strong></p>

// Middleware to protect sensitive routes
namespace App\Http\Middleware;

use Closure;

class EnsureTwoFactorAuthenticated
{
    public function handle($request, Closure $next)
    {
        if (!session('2fa_verified', false)) {
            return redirect()->route('2fa.prompt');
        }

        return $next($request);
    }
}
