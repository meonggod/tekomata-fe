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
        'country_label' => 'Negara',
        'country_optional' => 'Opsional',
        'country_hint' => 'Menentukan mata uang default dan kode telepon. Bisa diubah nanti.',
        'country_placeholder' => 'Pilih negaramu',
        'country_search' => 'Cari negara…',
        'country_no_results' => 'Tidak ada negara yang cocok',
        'country_clear' => 'Hapus pilihan',
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
        'currencies' => 'Mata uang',
        'products'   => 'Produk',
    ],

    'currencies' => [
        'title' => 'Mata uang',
        'subtitle' => 'Pilih mata uang yang dipakai perusahaanmu untuk menetapkan harga, dan tetapkan satu sebagai default.',
        'back_to_dashboard' => 'Kembali ke dasbor',
        'heading' => 'Mata uang tersedia',
        'enabled_label' => 'Aktif',
        'default_label' => 'Default',
        'default_badge' => 'Default',
        'enable' => 'Aktifkan',
        'disable' => 'Nonaktifkan',
        'make_default' => 'Jadikan default',
        'decimals' => ':count desimal',
        'empty' => 'Belum ada mata uang yang tersedia saat ini. Silakan coba lagi sebentar.',
        'none_enabled' => 'Kamu belum mengaktifkan mata uang apa pun — aktifkan satu di bawah untuk mulai menetapkan harga.',
        'default_locked_hint' => 'Mata uang default tidak bisa dinonaktifkan. Tetapkan mata uang lain sebagai default dulu.',
        'in_use_note' => 'Mata uang yang dipakai harga produk tidak bisa dinonaktifkan — penetapan harga produk hadir di story berikutnya, jadi untuk saat ini hanya default yang dilindungi.',
        'status_enabled' => 'Mata uang diaktifkan.',
        'status_disabled' => 'Mata uang dinonaktifkan.',
        'status_default_set' => 'Mata uang default diperbarui.',
    ],

    'categories' => [
        'title'                  => 'Kategori',
        'subtitle'               => 'Kelompokkan produkmu ke dalam kategori.',
        'back_to_products'       => 'Kembali ke produk',
        'back_to_categories'     => 'Kembali ke kategori',
        'add_category'           => 'Tambah kategori',
        'no_categories'          => 'Belum ada kategori — tambahkan kategori pertamamu.',
        'confirm_delete'         => 'Hapus kategori ini? Pengelompokannya akan dihapus, tapi produknya tetap ada.',
        'view'                   => 'Lihat',
        'edit'                   => 'Ubah',
        'delete'                 => 'Hapus',
        'create_title'           => 'Tambah kategori',
        'edit_title'             => 'Ubah kategori',
        'name_label'             => 'Nama',
        'code_label'             => 'Kode',
        'code_hint'              => 'Opsional — identifikasi singkat yang stabil, mis. BEV.',
        'description_label'      => 'Deskripsi',
        'active_label'           => 'Aktif',
        'active_hint'            => 'Kategori tidak aktif disembunyikan dari penugasan produk.',
        'save'                   => 'Simpan kategori',
        'cancel'                 => 'Batal',
        'status_active'          => 'Aktif',
        'status_inactive'        => 'Tidak aktif',
        'products_section'       => 'Produk dalam kategori ini',
        'no_products'            => 'Belum ada produk dalam kategori ini.',
        'remove_product'         => 'Hapus',
        'add_products_title'     => 'Tambah produk',
        'product_ids_label'      => 'Pilih produk yang akan ditambahkan',
        'add_products_submit'    => 'Tambahkan yang dipilih',
        'status_created'         => 'Kategori berhasil dibuat.',
        'status_updated'         => 'Kategori berhasil diperbarui.',
        'status_deleted'         => 'Kategori berhasil dihapus.',
        'status_products_added'  => 'Produk berhasil ditambahkan ke kategori.',
        'status_product_removed' => 'Produk berhasil dihapus dari kategori.',
    ],

    'products' => [
        'title'               => 'Produk',
        'subtitle'            => 'Kelola katalog produkmu.',
        'back_to_dashboard'   => 'Kembali ke dasbor',
        'warehouses'          => 'Gudang',
        'categories'          => 'Kategori',
        'add_product'         => 'Tambah produk',
        'search_placeholder'  => 'Cari produk…',
        'filter_apply'        => 'Cari',
        'filter_reset'        => 'Hapus',
        'no_products'         => 'Belum ada produk — tambahkan produk pertamamu.',
        'confirm_delete'      => 'Hapus produk ini? Riwayat stok akan tetap tersimpan.',
        'view'                => 'Lihat',
        'edit'                => 'Ubah',
        'delete'              => 'Hapus',
        'fractional_yes'      => 'desimal',
        'create_title'        => 'Tambah produk',
        'edit_title'          => 'Ubah produk',
        'name_label'          => 'Nama',
        'sku_label'           => 'SKU',
        'sku_hint'            => 'Opsional — kode referensimu sendiri.',
        'unit_label'          => 'Satuan',
        'unit_hint'           => 'Mis. pcs, kg, liter, karton',
        'fractional_label'    => 'Izinkan jumlah desimal',
        'fractional_hint'     => 'Aktifkan untuk satuan yang bisa dijual dalam pecahan, mis. 1,5 kg.',
        'price_label'         => 'Harga default',
        'currency_label'      => 'Mata uang',
        'no_currencies'       => 'Belum ada mata uang yang diaktifkan untuk perusahaanmu — buka Pengaturan → Mata Uang dulu.',
        'save'                => 'Simpan produk',
        'cancel'              => 'Batal',
        'back_to_products'    => 'Kembali ke produk',
        'back_to_product'     => 'Kembali ke produk',
        'stock_section'       => 'Stok per gudang',
        'no_stock'            => 'Belum ada stok yang dicatat.',
        'balance'             => 'Saldo',
        'adjust_title'        => 'Sesuaikan stok',
        'warehouse_label'     => 'Gudang',
        'no_warehouses'       => 'Belum ada gudang — tambahkan gudang dulu.',
        'delta_label'         => 'Penyesuaian jumlah',
        'delta_hint'          => 'Positif untuk menambah stok, negatif untuk mengurangi.',
        'reason_label'        => 'Alasan',
        'reason_import'       => 'Impor',
        'reason_manual_adjustment' => 'Penyesuaian manual',
        'reason_correction'   => 'Koreksi',
        'note_label'          => 'Catatan',
        'note_hint'           => 'Opsional — tambahkan konteks untuk penyesuaian ini.',
        'apply_adjustment'    => 'Terapkan penyesuaian',
        'movements_link'      => 'Lihat riwayat pergerakan',
        'movements_title'     => 'Pergerakan stok',
        'filter_warehouse'    => 'Gudang',
        'filter_from'         => 'Dari',
        'filter_to'           => 'Sampai',
        'no_movements'        => 'Tidak ada pergerakan ditemukan.',
        'col_date'            => 'Tanggal',
        'col_warehouse'       => 'Gudang',
        'col_delta'           => 'Jumlah',
        'col_reason'          => 'Alasan',
        'col_note'            => 'Catatan',
        'status_created'      => 'Produk berhasil dibuat.',
        'status_updated'      => 'Produk berhasil diperbarui.',
        'status_deleted'      => 'Produk berhasil dihapus.',
        'status_adjusted'     => 'Stok berhasil disesuaikan.',
        'new_balance'         => 'Saldo baru: :balance',
        'categories_label'    => 'Kategori',
        'categories_hint'     => 'Opsional — tambahkan produk ini ke satu atau beberapa kategori. Tahan Ctrl/Cmd untuk memilih lebih dari satu.',
        'update_categories'   => 'Perbarui kategori',
        'categories_updated'  => 'Kategori berhasil diperbarui.',
        'no_categories_hint'  => 'Belum ada kategori — tambahkan kategori dulu.',
    ],

    'warehouses' => [
        'title'           => 'Gudang',
        'subtitle'        => 'Kelola lokasi penyimpanan stok produkmu.',
        'back_to_products'=> 'Kembali ke produk',
        'add_warehouse'   => 'Tambah gudang',
        'no_warehouses'   => 'Belum ada gudang — tambahkan gudang pertamamu.',
        'confirm_delete'  => 'Hapus gudang ini?',
        'name_label'      => 'Nama',
        'code_label'      => 'Kode',
        'code_hint'       => 'Opsional — identifikasi singkat, mis. MAIN.',
        'active_label'    => 'Aktif',
        'active_hint'     => 'Gudang tidak aktif disembunyikan dari penyesuaian stok.',
        'create_title'    => 'Tambah gudang',
        'edit_title'      => 'Ubah gudang',
        'save'            => 'Simpan gudang',
        'cancel'          => 'Batal',
        'edit'            => 'Ubah',
        'delete'          => 'Hapus',
        'status_active'   => 'Aktif',
        'status_inactive' => 'Tidak aktif',
        'code_col'        => 'Kode',
        'status_col'      => 'Status',
        'status_created'  => 'Gudang berhasil dibuat.',
        'status_updated'  => 'Gudang berhasil diperbarui.',
        'status_deleted'  => 'Gudang berhasil dihapus.',
    ],

    'errors' => [
        'unavailable_title' => 'Sebentar lagi kami kembali',
        'unavailable_body' => 'tekomata sedang kesulitan menghubungi layanannya. Silakan coba lagi sebentar.',
        'try_again' => 'Coba lagi',

        // Modal kegagalan tak terduga (5xx) + blok kode referensi bersama.
        'modal_title' => 'Terjadi kesalahan',
        'modal_body' => 'Kami belum bisa menyelesaikannya barusan — masalahnya ada di sisi kami, bukan kamu. Tim kami sudah diberi tahu. Silakan coba lagi sebentar.',
        'ref_label' => 'Kode referensi',
        'ref_hint' => 'Sebutkan kode ini jika kamu menghubungi tim kami — ini membantu kami menemukan persis apa yang terjadi.',
        'copy' => 'Salin',
        'copied' => 'Tersalin',
        'dismiss' => 'Tutup',
    ],

];
