<?php

/*
|--------------------------------------------------------------------------
| Teks Bahasa Indonesia  (lang/id/messages.php)
|--------------------------------------------------------------------------
| Semua teks Bahasa Indonesia ada di sini. Untuk memperbaiki kalimat, ubah
| nilai di sebelah kanan. Jaga agar key-nya sama persis dengan
| lang/en/messages.php supaya kedua bahasa tetap sinkron.
| Dipakai di Blade sebagai __('messages.<key.path>').
*/

return [

    'nav' => [
        'sign_in' => 'Masuk',
        'get_started' => 'Mulai sekarang',
    ],

    'landing' => [

        'hero' => [
            'eyebrow' => 'Asisten AI langsung di WhatsApp',
            'title' => 'Tanya apa saja soal katalogmu — langsung di WhatsApp.',
            'subtitle' => 'tekomata menjawab pertanyaan tentang produk, stok, dan harga milikmu dengan bahasa sehari-hari. Tanpa buka dashboard, tanpa scroll spreadsheet. Cukup tanya.',
            'cta_primary' => 'Mulai sekarang',
            'cta_secondary' => 'Masuk',
        ],

        'features' => [
            'heading' => 'Apa yang bisa kamu tanyakan',
            'subheading' => 'tekomata mengubah katalogmu menjadi jawaban. Ketik pertanyaan di WhatsApp dan dapatkan balasan jelas dalam hitungan detik.',
            'items' => [
                'stock' => [
                    'title' => 'Stok terkini, per gudang',
                    'body' => '“Stok produk X ada berapa, dan di gudang mana?” — dapatkan jumlah pasti di setiap lokasi.',
                ],
                'location' => [
                    'title' => 'Di mana stoknya berada',
                    'body' => 'Langsung tahu gudang mana yang menyimpan apa, jadi kamu hanya menjanjikan yang benar-benar bisa dikirim.',
                ],
                'pricing' => [
                    'title' => 'Harga yang tepat, tiap tier',
                    'body' => '“Berapa harga produk X untuk pembeli ini?” — tekomata memilih tier harga yang benar untukmu.',
                ],
                'instant' => [
                    'title' => 'Jawaban instan & sederhana',
                    'body' => 'Tanpa menu, tanpa kode. Tanya seperti bertanya ke rekan kerja, dan dapatkan jawaban langsung.',
                ],
                'whatsapp' => [
                    'title' => 'Langsung di WhatsApp',
                    'body' => 'Berada di tempat timmu sudah bekerja. Tidak ada aplikasi baru — cukup chat asistennya.',
                ],
                'owned' => [
                    'title' => 'Datamu, sumber kebenaranmu',
                    'body' => 'Impor produk, gudang, dan tier harga sekali saja. tekomata menyimpannya dan menjaga jawaban tetap konsisten.',
                ],
            ],
        ],

        'why' => [
            'heading' => 'Kenapa bisnis memilih tekomata',
            'items' => [
                'speed' => [
                    'title' => 'Jawaban dalam hitungan detik',
                    'body' => 'Berhenti mengubek-ubek file atau menelpon gudang. Tanya sekali, putuskan lebih cepat.',
                ],
                'accuracy' => [
                    'title' => 'Satu jawaban terpercaya',
                    'body' => 'Stok, lokasi, dan harga dari satu sumber — tanpa spreadsheet yang saling bertentangan.',
                ],
                'nolearning' => [
                    'title' => 'Tidak perlu belajar',
                    'body' => 'Kalau timmu bisa pakai WhatsApp, mereka bisa pakai tekomata. Tanpa pelatihan.',
                ],
            ],
        ],

        'how' => [
            'heading' => 'Cara kerjanya',
            'steps' => [
                'import' => [
                    'title' => '1 · Impor katalogmu',
                    'body' => 'Masukkan produk, gudang, stok per gudang, dan tier harga.',
                ],
                'ask' => [
                    'title' => '2 · Tanya di WhatsApp',
                    'body' => 'Chat asistennya dengan bahasa sehari-hari — stok, lokasi, atau harga.',
                ],
                'answer' => [
                    'title' => '3 · Dapat jawaban instan',
                    'body' => 'tekomata mencarikannya dan membalas dalam hitungan detik, langsung di chat.',
                ],
            ],
        ],

        'pricing' => [
            'heading' => 'Bayar sesuai pemakaian',
            'body' => 'Setiap jawaban adalah satu biaya pemakaian sederhana — mulai tanpa komitmen. Saat bisnismu tumbuh, paket langganan menurunkan tarif per pertanyaan. Coba gratis, naik paket saat sudah menguntungkan.',
        ],

        'cta' => [
            'heading' => 'Siap memindahkan katalogmu ke WhatsApp?',
            'body' => 'Buat akun dan ajukan pertanyaan pertamamu dalam hitungan menit.',
            'button' => 'Mulai sekarang',
        ],

        'footer' => [
            'tagline' => 'AI di WhatsApp untuk katalogmu.',
        ],
    ],

    'auth' => [
        'sign_in_title' => 'Masuk ke tekomata',
        'sign_in_subtitle' => 'Selamat datang kembali — masuk ke akunmu.',
        'no_account' => 'Belum punya akun?',
        'email_label' => 'Email',
        'password_label' => 'Kata sandi',
        'forgot_password' => 'Lupa kata sandi?',
        'remember_me' => 'Ingat saya',
        'submit' => 'Masuk',
    ],

    'forgot' => [
        'title' => 'Lupa kata sandi?',
        'subtitle' => 'Masukkan emailmu dan kami akan mengirim tautan untuk mengaturnya ulang.',
        'email_label' => 'Email',
        'submit' => 'Kirim tautan reset',
        'secure_note' => 'Demi keamananmu, tautan reset akan kedaluwarsa dalam waktu singkat.',
        'remembered' => 'Sudah ingat kata sandimu?',
        'different_email' => 'Pakai email lain',
        'check_email_title' => 'Cek emailmu',
        'check_email_body' => 'Jika :email terhubung dengan sebuah akun, kami telah mengirimkan tautan reset kata sandi ke alamat itu. Klik tautannya untuk membuat kata sandi baru.',
    ],

    'reset' => [
        'title' => 'Buat kata sandi baru',
        'subtitle' => 'Pilih kata sandi baru untuk akunmu.',
        'password_label' => 'Kata sandi baru',
        'password_hint' => 'Gunakan minimal 8 karakter.',
        'submit' => 'Reset kata sandi',
        'success' => 'Kata sandimu sudah direset. Silakan masuk.',
        'request_new' => 'Minta tautan baru',
        'back_to_sign_in' => 'Kembali ke halaman masuk',
    ],

    'register' => [
        'title' => 'Buat akunmu',
        'subtitle' => 'Cukup email dan kata sandi — detail bisnis bisa ditambahkan nanti.',
        'email_label' => 'Email',
        'password_label' => 'Kata sandi',
        'password_hint' => 'Gunakan minimal 8 karakter.',
        'submit' => 'Buat akun',
        'have_account' => 'Sudah punya akun?',
        'different_email' => 'Pakai email lain',
        'secure_note' => 'Kami akan mengirim tautan aman untuk mengonfirmasi akunmu.',
        'check_email_title' => 'Cek emailmu',
        'check_email_body' => 'Jika :email dapat didaftarkan, kami telah mengirimkan tautan verifikasi ke alamat itu. Klik tautannya untuk menyelesaikan pembuatan akunmu.',
        'verified' => 'Emailmu sudah terverifikasi. Silakan masuk untuk melanjutkan.',
        'verify_failed_title' => 'Tautan ini sudah tidak berlaku',
        'verify_failed_body' => 'Tautan verifikasi tidak valid, sudah kedaluwarsa, atau sudah pernah dipakai. Silakan daftar lagi untuk mendapatkan tautan baru.',
        'verify_failed_cta' => 'Kembali ke pendaftaran',
    ],

    'dashboard' => [
        'title' => 'Dasbor',
        'sign_out' => 'Keluar',
        'placeholder' => 'Area terproteksi — hanya bisa diakses dengan sesi tekomata yang valid. Panel katalog, pemakaian, dan tagihan dibangun di sini seiring story ClickUp masuk.',
        'company' => 'Perusahaan',
        'switch_company' => 'Ganti',
    ],

    'errors' => [
        'unavailable_title' => 'Sebentar lagi kami kembali',
        'unavailable_body' => 'tekomata sedang kesulitan menghubungi layanannya. Silakan coba lagi sebentar.',
        'try_again' => 'Coba lagi',
    ],

];
