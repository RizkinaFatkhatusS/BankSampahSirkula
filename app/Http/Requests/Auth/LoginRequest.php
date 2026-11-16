<?php

namespace App\Http\Requests\Auth;

use App\Models\LogAkses;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Memeriksa email dan kata sandi, lalu status akun. Pesan status akun (ditangguhkan, nonaktif, dst.)
     * baru ditampilkan setelah kata sandi benar agar email terdaftar tidak bocor (FR-02).
     * Mengembalikan pengguna; proses login sesungguhnya dilakukan controller (2FA bisa menyusul).
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $user = User::where('email', $this->string('email')->lower()->toString())->first();

        if (! $user || ! $user->password || ! Hash::check($this->string('password')->toString(), $user->password)) {
            RateLimiter::hit($this->throttleKey(), 60);
            LogAkses::catat('login_gagal', ['email' => $this->string('email')->toString()]);

            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        if ($pesan = $user->pesanStatusLogin()) {
            RateLimiter::hit($this->throttleKey(), 60);
            LogAkses::catat('login_gagal', ['email' => $user->email, 'alasan' => $user->status_akun], $user->id);

            throw ValidationException::withMessages(['email' => $pesan]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /** Admin dan Super Admin dibatasi lebih ketat (3 percobaan), pengguna lain 5. */
    private function maksPercobaan(): int
    {
        $role = User::where('email', $this->string('email')->lower()->toString())->value('role');

        return in_array($role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true) ? 3 : 5;
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $this->maksPercobaan())) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
