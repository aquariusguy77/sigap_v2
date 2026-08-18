<?php

/**
 * Pengaturan pembuatan berkas PDF.
 *
 * Catatan penting untuk lingkungan serverless seperti Vercel: seluruh folder
 * proyek bersifat baca-saja kecuali /tmp. Karena dompdf perlu menulis cache
 * font saat bekerja, seluruh jalur tulis di bawah ini diarahkan ke /tmp.
 */

$writable = env('DOMPDF_TEMP_DIR', sys_get_temp_dir());

return [

    'show_warnings' => false,

    'orientation' => 'portrait',

    'defines' => [

        // Folder tulis dompdf. Wajib berada di /tmp saat berjalan di Vercel.
        'font_dir' => $writable . '/dompdf-fonts',
        'font_cache' => $writable . '/dompdf-fonts',
        'temp_dir' => $writable,
        'chroot' => realpath(base_path()),

        'enable_font_subsetting' => true,

        'default_media_type' => 'print',
        'default_paper_size' => 'a4',

        // DejaVu Sans mendukung huruf beraksen dan tanda baca khusus.
        'default_font' => 'DejaVu Sans',

        'dpi' => 96,

        'enable_php' => false,
        'enable_javascript' => false,

        // Diperlukan agar lambang instansi berbentuk data URI ikut tercetak.
        'enable_remote' => false,
        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
        ],

        'enable_html5_parser' => true,
    ],

];
