<?php

/*
 * Pesan validasi bawaan dalam bahasa Indonesia.
 *
 * Tanpa berkas ini Laravel memakai bahasa cadangan (Inggris), sehingga
 * petugas melihat pesan seperti "The nama lengkap field is required."
 * Pesan khusus tiap formulir tetap diatur di kelas Request masing-masing;
 * berkas ini hanya mengisi aturan yang belum diberi pesan sendiri.
 */

return [
    'accepted' => 'Kolom :attribute harus disetujui.',
    'after' => 'Kolom :attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => 'Kolom :attribute harus berupa tanggal yang sama atau setelah :date.',
    'alpha' => 'Kolom :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Kolom :attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => 'Kolom :attribute hanya boleh berisi huruf dan angka.',
    'array' => 'Kolom :attribute harus berupa daftar.',
    'before' => 'Kolom :attribute harus berupa tanggal sebelum :date.',
    'before_or_equal' => 'Kolom :attribute harus berupa tanggal yang sama atau sebelum :date.',
    'between' => [
        'array' => 'Kolom :attribute harus berisi antara :min dan :max item.',
        'file' => 'Ukuran :attribute harus antara :min dan :max kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai antara :min dan :max.',
        'string' => 'Kolom :attribute harus berisi antara :min dan :max karakter.',
    ],
    'boolean' => 'Kolom :attribute harus bernilai ya atau tidak.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi saat ini salah.',
    'date' => 'Kolom :attribute harus berupa tanggal yang valid.',
    'date_format' => 'Kolom :attribute harus sesuai format :format.',
    'different' => 'Kolom :attribute dan :other harus berbeda.',
    'digits' => 'Kolom :attribute harus terdiri atas :digits angka.',
    'digits_between' => 'Kolom :attribute harus terdiri atas :min sampai :max angka.',
    'email' => 'Kolom :attribute harus berupa alamat email yang valid.',
    'exists' => ':attribute yang dipilih tidak ditemukan.',
    'file' => 'Kolom :attribute harus berupa berkas.',
    'filled' => 'Kolom :attribute wajib diisi.',
    'image' => 'Kolom :attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'integer' => 'Kolom :attribute harus berupa bilangan bulat.',
    'max' => [
        'array' => 'Kolom :attribute tidak boleh berisi lebih dari :max item.',
        'file' => 'Ukuran :attribute tidak boleh lebih dari :max kilobita.',
        'numeric' => 'Kolom :attribute tidak boleh lebih dari :max.',
        'string' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
    ],
    'mimes' => 'Kolom :attribute harus berupa berkas berjenis: :values.',
    'mimetypes' => 'Kolom :attribute harus berupa berkas berjenis: :values.',
    'min' => [
        'array' => 'Kolom :attribute harus berisi sedikitnya :min item.',
        'file' => 'Ukuran :attribute sedikitnya :min kilobita.',
        'numeric' => 'Kolom :attribute sedikitnya bernilai :min.',
        'string' => 'Kolom :attribute sedikitnya berisi :min karakter.',
    ],
    'not_in' => ':attribute yang dipilih tidak valid.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'present' => 'Kolom :attribute harus ada.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_if' => 'Kolom :attribute wajib diisi bila :other bernilai :value.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other bernilai :values.',
    'required_with' => 'Kolom :attribute wajib diisi bila :values diisi.',
    'required_without' => 'Kolom :attribute wajib diisi bila :values tidak diisi.',
    'same' => 'Kolom :attribute dan :other harus sama.',
    'size' => [
        'array' => 'Kolom :attribute harus berisi :size item.',
        'file' => 'Ukuran :attribute harus :size kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai :size.',
        'string' => 'Kolom :attribute harus berisi :size karakter.',
    ],
    'string' => 'Kolom :attribute harus berupa teks.',
    'unique' => ':attribute sudah digunakan.',
    'uploaded' => ':attribute gagal diunggah.',
    'url' => 'Kolom :attribute harus berupa tautan yang valid.',

    'custom' => [],

    'attributes' => [
        'email' => 'email',
        'password' => 'kata sandi',
        'refugee_id' => 'pengungsi',
        'category' => 'kategori penempatan',
        'community_house' => 'Community House',
        'address' => 'alamat',
        'latitude' => 'lintang',
        'longitude' => 'bujur',
        'entered_at' => 'tanggal masuk',
        'exited_at' => 'tanggal keluar',
        'placement_status' => 'status penempatan',
        'document_type' => 'jenis dokumen',
        'file_name' => 'nama berkas',
        'verification_status' => 'status verifikasi',
        'uploaded_at' => 'tanggal unggah',
        'uploaded_file' => 'berkas unggahan',
        'notes' => 'catatan',
    ],
];
