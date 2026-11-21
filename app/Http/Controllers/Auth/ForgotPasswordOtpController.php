<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class ForgotPasswordOtpController extends Controller
{
    private const SESSION_KEY = 'password_reset';

    private const OTP_COOLDOWN_SECONDS = 10;

    private const RECOVERY_SESSION_MINUTES = 30;

    private const GENERIC_MESSAGE = 'Jika email tersebut terdaftar, kode verifikasi akan dikirim ke email yang terkait dengan akun.';

    private const MAIL_UNAVAILABLE_MESSAGE = 'Layanan pengiriman email belum tersedia. Tidak ada kode verifikasi yang dikirim.';

    private const MAIL_DELIVERY_FAILED_MESSAGE = 'Kode verifikasi belum dapat dikirim karena layanan email sedang bermasalah. Silakan coba lagi.';

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('account.edit');
        }

        return view('auth.forgot-password');
    }

    public function fromAccount(Request $request): RedirectResponse
    {
        return $this->store($request);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()) {
            $request->merge(['identity' => $request->user()->email]);
        }
        $identity = Str::lower(trim($request->validate([
            'identity' => ['required', 'string', 'email', 'max:255'],
        ], ['identity.email' => 'Masukkan alamat email terdaftar yang valid.'])['identity']));
        $key = hash('sha256', $identity.'|'.$request->ip());
        if ($this->limited($key)) {
            return back()->with('status', 'Permintaan terlalu cepat. Silakan tunggu sebelum mencoba kembali.');
        }
        RateLimiter::hit("password-reset:cooldown:$key", self::OTP_COOLDOWN_SECONDS);
        RateLimiter::hit("password-reset:hourly:$key", 3600);
        $request->session()->forget(self::SESSION_KEY);
        $user = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$identity])->first();
        if ($user && ! $this->issue($request, $user)) {
            if (config('mail.default') === 'log') {
                return redirect()->route('password.otp.create')->with('status', self::MAIL_UNAVAILABLE_MESSAGE);
            }

            $this->clearRateLimits($key);

            return redirect()->route('password.request')
                ->withInput()
                ->withErrors(['identity' => self::MAIL_DELIVERY_FAILED_MESSAGE]);
        }

        $status = match (true) {
            config('mail.default') === 'log' => self::MAIL_UNAVAILABLE_MESSAGE,
            default => self::GENERIC_MESSAGE,
        };

        return redirect()->route('password.otp.create')->with('status', $status);
    }

    public function verifyForm(): View
    {
        return view('auth.verify-reset-otp');
    }

    public function verify(Request $request): RedirectResponse
    {
        $code = $request->validate(['otp' => ['required', 'digits:6']])['otp'];
        $otp = $this->pending($request);
        if (! $otp || $otp->expires_at->isPast()) {
            return back()->withErrors(['otp' => 'Kode verifikasi sudah kedaluwarsa. Silakan minta kode baru.']);
        }
        if ($otp->consumed_at || $otp->attempts >= 5) {
            return back()->withErrors(['otp' => 'Kode tidak berlaku. Silakan minta kode baru.']);
        }
        if (! Hash::check($code, $otp->otp_hash)) {
            $otp->increment('attempts');
            if ($otp->fresh()->attempts >= 5) {
                $otp->update(['consumed_at' => now()]);
            }

            return back()->withErrors(['otp' => 'Kode verifikasi tidak sesuai.']);
        }
        $otp->update(['verified_at' => now()]);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, ['otp_id' => $otp->id, 'verified_until' => now()->addMinutes(10)->timestamp]);

        return redirect()->route('password.reset.create');
    }

    public function resend(Request $request): RedirectResponse
    {
        $otp = $this->recoveryOtp($request);
        if (! $otp) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('password.request')
                ->withErrors(['identity' => 'Sesi pemulihan sudah berakhir. Silakan masukkan email terdaftar kembali.']);
        }
        $key = hash('sha256', $otp->user->email.'|'.$request->ip());
        if ($this->limited($key)) {
            return back()->withErrors(['otp' => 'Permintaan terlalu cepat. Silakan tunggu sebelum meminta kode baru.']);
        }
        RateLimiter::hit("password-reset:cooldown:$key", self::OTP_COOLDOWN_SECONDS);
        RateLimiter::hit("password-reset:hourly:$key", 3600);
        if (! $this->issue($request, $otp->user)) {
            if (config('mail.default') === 'log') {
                return back()->with('status', self::MAIL_UNAVAILABLE_MESSAGE);
            }

            $this->clearRateLimits($key);

            return back()->withErrors(['otp' => self::MAIL_DELIVERY_FAILED_MESSAGE]);
        }

        return back()->with(
            'status', 'Jika email terdaftar, kode verifikasi baru akan dikirim.'
        );
    }

    public function resetForm(Request $request): View|RedirectResponse
    {
        if (! $this->authorized($request)) {
            return redirect()->route('password.request')->withErrors(['identity' => 'Verifikasi kode diperlukan sebelum membuat kata sandi baru.']);
        }

        return view('auth.reset-password');
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi belum sama. Isi kedua kolom dengan kata sandi yang persis sama, termasuk huruf besar, huruf kecil, dan spasi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
        ]);
        $otp = $this->authorized($request);
        if (! $otp) {
            return redirect()->route('password.request')->withErrors(['identity' => 'Sesi reset tidak berlaku. Silakan mulai kembali.']);
        }
        DB::transaction(function () use ($otp, $validated): void {
            $locked = PasswordResetOtp::with('user')->lockForUpdate()->findOrFail($otp->id);
            abort_if($locked->consumed_at || ! $locked->verified_at, 403);
            $locked->user->forceFill(['password' => Hash::make($validated['password']), 'remember_token' => Str::random(60)])->save();
            PasswordResetOtp::where('user_id', $locked->user_id)->whereNull('consumed_at')->update(['consumed_at' => now()]);
        });
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerateToken();

        if ($request->user()) {
            $request->session()->regenerate();

            return redirect()->route('account.edit')->with('success', 'Kata sandi berhasil diperbarui melalui verifikasi email. Gunakan kata sandi baru saat login berikutnya.');
        }

        return redirect()->route('login')->with('auth_feedback', ['title' => 'Kata Sandi Berhasil Diubah', 'message' => 'Kata sandi berhasil diubah. Silakan masuk menggunakan kata sandi baru.']);
    }

    private function issue(Request $request, User $user): bool
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp = PasswordResetOtp::create(['user_id' => $user->id, 'otp_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(10)]);

        if (config('mail.default') === 'log') {
            $otp->update(['consumed_at' => now()]);

            return false;
        }

        try {
            Mail::to($user->email)->send(new PasswordResetOtpMail($code));
        } catch (Throwable $exception) {
            $otp->update(['consumed_at' => now()]);
            report($exception);

            return false;
        }

        PasswordResetOtp::where('user_id', $user->id)
            ->whereKeyNot($otp->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
        $request->session()->put(self::SESSION_KEY, ['otp_id' => $otp->id, 'pending_until' => now()->addMinutes(10)->timestamp]);

        return true;
    }

    private function pending(Request $request): ?PasswordResetOtp
    {
        $state = $request->session()->get(self::SESSION_KEY, []);
        if (! isset($state['otp_id'], $state['pending_until']) || $state['pending_until'] < now()->timestamp) {
            return null;
        }

        return PasswordResetOtp::with('user')
            ->when($request->user(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->find($state['otp_id']);
    }

    private function recoveryOtp(Request $request): ?PasswordResetOtp
    {
        $state = $request->session()->get(self::SESSION_KEY, []);

        if (! isset($state['otp_id'])) {
            return null;
        }

        return PasswordResetOtp::with('user')
            ->when($request->user(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->whereKey($state['otp_id'])
            ->where('created_at', '>=', now()->subMinutes(self::RECOVERY_SESSION_MINUTES))
            ->first();
    }

    private function authorized(Request $request): ?PasswordResetOtp
    {
        $state = $request->session()->get(self::SESSION_KEY, []);
        if (! isset($state['otp_id'], $state['verified_until']) || $state['verified_until'] < now()->timestamp) {
            return null;
        }

        return PasswordResetOtp::whereKey($state['otp_id'])
            ->when($request->user(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->whereNotNull('verified_at')->whereNull('consumed_at')->first();
    }

    private function limited(string $key): bool
    {
        return RateLimiter::tooManyAttempts("password-reset:cooldown:$key", 1) || RateLimiter::tooManyAttempts("password-reset:hourly:$key", 5);
    }

    private function clearRateLimits(string $key): void
    {
        RateLimiter::clear("password-reset:cooldown:$key");
        RateLimiter::clear("password-reset:hourly:$key");
    }
}
