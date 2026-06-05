<?php

/*
|--------------------------------------------------------------------------
| Katalog error API → Bahasa Indonesia  (lang/id/errors.php)
|--------------------------------------------------------------------------
| Go API mengembalikan kegagalan sebagai { "error": { "code", ... } } di mana
| `code` adalah key i18n yang stabil (BUKAN pesan mentah). Frontend menampilkan
| teks berdasarkan code, jadi setiap code yang bisa muncul di panel diterjemahkan
| di sini. Jaga key tetap sama dengan lang/en/errors.php. Code bertingkat memakai
| notasi titik (mis. __('errors.auth.invalid_token')). Code per-field bisa
| membawa params, mis. :min / :max.
| Sumber kebenaran: backend `documentation/error-catalog.md`.
*/

return [

    // Error umum / tingkat atas.
    'generic' => 'Terjadi kesalahan. Silakan coba lagi.',
    'validation_failed' => 'Mohon periksa kolom yang ditandai lalu coba lagi.',
    'rate_limited' => 'Terlalu banyak percobaan. Mohon tunggu sebentar lalu coba lagi.',
    'bad_request' => 'Terjadi kesalahan. Silakan coba lagi.',
    'unauthorized' => 'Sesimu sudah berakhir. Silakan masuk kembali.',
    'forbidden' => 'Kamu tidak punya akses ke sana.',

    // Autentikasi.
    'auth' => [
        'invalid_token' => 'Tautan verifikasi ini tidak valid atau sudah kedaluwarsa.',
        'invalid_credentials' => 'Email atau kata sandi salah.',
        'email_not_verified' => 'Verifikasi emailmu dulu — cek kotak masuk untuk tautannya.',
        'invalid_refresh_token' => 'Sesimu sudah berakhir. Silakan masuk kembali.',
    ],

    // Kode validasi per-field (dipakai di error.fields[].code).
    'validation' => [
        'required' => 'Kolom ini wajib diisi.',
        'email' => 'Mohon masukkan alamat email yang valid.',
        'too_short' => 'Minimal :min karakter.',
        'too_long' => 'Maksimal :max karakter.',
    ],

];
