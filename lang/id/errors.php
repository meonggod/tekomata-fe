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
        'invalid_reset_token' => 'Tautan reset kata sandi ini tidak valid, sudah kedaluwarsa, atau sudah pernah dipakai.',
        'invalid_credentials' => 'Email atau kata sandi salah.',
        'email_not_verified' => 'Verifikasi emailmu dulu — cek kotak masuk untuk tautannya.',
        'invalid_refresh_token' => 'Sesimu sudah berakhir. Silakan masuk kembali.',
    ],

    // Katalog (daftar master negara / mata uang).
    'catalog' => [
        'currency_not_active' => 'Mata uang itu belum tersedia di platform.',
    ],

    // Pengaktifan mata uang per perusahaan.
    'company' => [
        'currency_already_enabled' => 'Mata uang itu sudah aktif untuk perusahaanmu.',
        'currency_not_enabled' => 'Mata uang itu belum aktif untuk perusahaanmu.',
        'cannot_disable_default_currency' => 'Kamu tidak bisa menonaktifkan mata uang default — tetapkan default lain dulu.',
    ],

    // Kategori.
    'category' => [
        'not_found' => 'Kategori tidak ditemukan.',
        'name_taken' => 'Kategori dengan nama itu sudah ada.',
    ],

    // Produk.
    'product' => [
        'not_found' => 'Produk tidak ditemukan.',
        'sku_taken' => 'SKU itu sudah dipakai — pilih yang lain.',
    ],

    // Gudang.
    'warehouse' => [
        'not_found' => 'Gudang tidak ditemukan.',
        'name_taken' => 'Gudang dengan nama itu sudah ada.',
    ],

    // Pengaturan perusahaan.
    'settings' => [
        'email_not_found'        => 'Email tidak ditemukan.',
        'email_taken'            => 'Alamat email itu sudah ada dalam daftarmu.',
        'last_email'             => 'Kamu harus mempertahankan setidaknya satu email notifikasi.',
        'whatsapp_not_found'     => 'Nomor WhatsApp tidak ditemukan.',
        'whatsapp_taken'         => 'Nomor itu sudah ada dalam daftarmu.',
        'last_whatsapp_number'   => 'Kamu harus mempertahankan setidaknya satu nomor WhatsApp.',
    ],

    // Dompet IDR prabayar.
    'wallet' => [
        'invalid_amount'      => 'Mohon masukkan jumlah yang valid dan lebih dari nol.',
        'insufficient_reward' => 'Jumlah itu melebihi saldo reward Anda. Masukkan jumlah yang lebih kecil.',
        'withdraw_not_allowed' => 'Penarikan memerlukan bisnis terverifikasi (KYB) dan rekening bank terlebih dahulu.',
        'payment_unavailable'  => 'Penyedia pembayaran sedang tidak tersedia. Silakan coba lagi sebentar.',
    ],

    // Omnichannel / inbox.
    'omnichannel' => [
        'conversation_not_found'  => 'Percakapan tidak ditemukan.',
        'send_window_closed'      => 'Jendela pesan sudah ditutup. Pelanggan harus mengirim pesan dulu untuk membukanya kembali.',
        'channel_not_registered'  => 'Kanal ini belum terhubung. Atur di Pengaturan terlebih dahulu.',
    ],

    // Kode validasi per-field (dipakai di error.fields[].code).
    'validation' => [
        'required'             => 'Kolom ini wajib diisi.',
        'email'                => 'Mohon masukkan alamat email yang valid.',
        'too_short'            => 'Minimal :min karakter.',
        'too_long'             => 'Maksimal :max karakter.',
        'invalid_country'      => 'Mohon pilih negara yang valid.',
        'decimal'              => 'Mohon masukkan angka desimal yang valid.',
        'fraction_not_allowed' => 'Satuan ini tidak mengizinkan jumlah desimal.',
        'invalid_value'        => 'Mohon pilih opsi yang valid.',
        'invalid_phone'        => 'Mohon masukkan nomor telepon yang valid dalam format E.164 (mis. +6281234567890).',
        'invalid_timezone'     => 'Mohon pilih zona waktu yang valid.',
        'time_range'           => 'Waktu buka harus sebelum waktu tutup.',
        'overlap'              => 'Slot waktu pada hari yang sama tidak boleh tumpang tindih.',
    ],

];
