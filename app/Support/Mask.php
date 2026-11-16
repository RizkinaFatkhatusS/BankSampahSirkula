<?php

namespace App\Support;

/** Penyamaran kontak Customer bagi Admin tanpa hak akses customer.kontak (FR-40). */
final class Mask
{
    public static function email(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return '';
        }
        [$lokal, $domain] = explode('@', $email, 2);

        return mb_substr($lokal, 0, 1).str_repeat('*', max(2, mb_strlen($lokal) - 1)).'@'.$domain;
    }

    public static function hp(?string $hp): string
    {
        $hp = (string) $hp;
        if (strlen($hp) < 7) {
            return str_repeat('*', strlen($hp));
        }

        return substr($hp, 0, 4).str_repeat('*', strlen($hp) - 6).substr($hp, -2);
    }
}
