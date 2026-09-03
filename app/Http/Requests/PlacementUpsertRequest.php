<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlacementUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categories = array_keys(config('sigap.reference.placement_categories', []));
        $houses = config('sigap.reference.community_houses', []);

        return [
            'refugee_id' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in($categories)],

            // Wajib bagi pengungsi berfasilitas IOM, diabaikan bagi yang mandiri.
            'community_house' => [
                Rule::requiredIf(fn () => $this->input('category') === 'iom'),
                'nullable',
                Rule::in($houses),
            ],

            /*
             * Wajib bagi pengungsi mandiri. Alamat inilah yang dipakai petugas
             * untuk menemukan rumahnya saat pengawasan lapangan, jadi tidak
             * cukup satu dua kata.
             */
            'address' => [
                Rule::requiredIf(fn () => $this->input('category') === 'mandiri'),
                'nullable',
                'string',
                'min:10',
                'max:500',
            ],

            // Opsional. Bila diisi, titik peta jadi tepat, bukan hasil pencarian alamat.
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // Kolom bantu; isinya sudah diurai menjadi lintang dan bujur di atas.
            'coordinate_paste' => ['nullable', 'string', 'max:500'],

            'entered_at' => ['required', 'date', 'before_or_equal:today'],
            'exited_at' => ['nullable', 'date', 'after_or_equal:entered_at'],
            'placement_status' => ['required', Rule::in(config('sigap.reference.placement_statuses', []))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lat = $this->input('latitude');
            $lng = $this->input('longitude');

            // Satu koordinat tanpa pasangannya tidak menunjuk ke mana pun.
            if (filled($lat) xor filled($lng)) {
                $validator->errors()->add(
                    'latitude',
                    'Lintang dan bujur harus diisi berpasangan, atau dikosongkan keduanya.'
                );
            }
        });
    }

    /**
     * Membaca titik koordinat dari teks yang ditempel petugas.
     *
     * Alur yang dipakai di lapangan: buka Google Maps di titik rumah, tekan
     * lama, lalu salin. Yang tersalin bisa berupa sepasang angka, bisa pula
     * tautan peta. Keduanya diterima di sini supaya petugas tidak perlu
     * memisahkan lintang dan bujur sendiri.
     *
     * Tautan pendek maps.app.goo.gl tidak dapat dibaca karena titiknya baru
     * muncul setelah tautan itu diikuti; gunakan salin koordinat, bukan bagikan.
     *
     * @return array{0: string, 1: string}|null
     */
    protected function parseCoordinate(string $value): ?array
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Bentuk tautan Google Maps: .../@-7.348123,112.727456,17z
        if (preg_match('/@(-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)/', $value, $m)) {
            return [$m[1], $m[2]];
        }

        // Sepasang angka, baik ditempel apa adanya maupun dari parameter q=
        if (preg_match('/(-?\d{1,3}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/', $value, $m)) {
            return [$m[1], $m[2]];
        }

        return null;
    }

    protected function prepareForValidation(): void
    {
        $category = strtolower(trim((string) $this->input('category'))) === 'mandiri' ? 'mandiri' : 'iom';

        /*
         * Kolom tempel diproses lebih dulu, tetapi tidak menimpa lintang dan
         * bujur yang sudah terisi — nilai yang sudah ada lebih berhak.
         */
        if ($category === 'mandiri'
            && blank($this->input('latitude'))
            && blank($this->input('longitude'))) {
            $parsed = $this->parseCoordinate((string) $this->input('coordinate_paste', ''));

            if ($parsed !== null) {
                $this->merge(['latitude' => $parsed[0], 'longitude' => $parsed[1]]);
            }
        }

        $this->merge([
            'category' => $category,
            // Kolom milik kategori lain dikosongkan agar tidak tersimpan menggantung.
            'community_house' => $category === 'iom' ? trim((string) $this->input('community_house')) : null,
            'address' => $category === 'mandiri' ? trim((string) $this->input('address')) : null,
            'latitude' => $category === 'mandiri' ? $this->blankToNull($this->input('latitude')) : null,
            'longitude' => $category === 'mandiri' ? $this->blankToNull($this->input('longitude')) : null,
            'notes' => trim((string) $this->input('notes')),
        ]);
    }

    protected function blankToNull(mixed $value): mixed
    {
        return trim((string) $value) === '' ? null : $value;
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Kategori penempatan wajib dipilih.',
            'category.in' => 'Kategori penempatan harus Fasilitas IOM atau Mandiri.',
            'community_house.required' => 'Community House wajib dipilih untuk pengungsi berfasilitas IOM.',
            'community_house.in' => 'Community House harus CH Puspa Agro atau CH Green Bamboo.',
            'address.required' => 'Alamat tempat tinggal wajib diisi untuk pengungsi mandiri.',
            'address.min' => 'Tulis alamat selengkap mungkin agar petugas dapat menemukannya di lapangan.',
            'latitude.numeric' => 'Lintang harus berupa angka, contohnya -7.348123.',
            'longitude.numeric' => 'Bujur harus berupa angka, contohnya 112.727456.',
            'entered_at.required' => 'Tanggal masuk wajib diisi.',
            'entered_at.before_or_equal' => 'Tanggal masuk tidak boleh melewati hari ini.',
            'exited_at.after_or_equal' => 'Tanggal keluar harus sama atau setelah tanggal masuk.',
            'placement_status.in' => 'Status penempatan harus Aktif atau Perlu Verifikasi.',
        ];
    }

    public function payload(): array
    {
        $category = (string) $this->input('category');

        return [
            'refugee_id' => (string) $this->input('refugee_id'),
            'category' => $category,
            'community_house' => $category === 'iom' ? (string) $this->input('community_house') : null,
            'address' => $category === 'mandiri' ? (string) $this->input('address') : null,
            'latitude' => $category === 'mandiri' ? $this->input('latitude') : null,
            'longitude' => $category === 'mandiri' ? $this->input('longitude') : null,
            'entered_at' => $this->input('entered_at'),
            'exited_at' => $this->input('exited_at'),
            'placement_status' => (string) $this->input('placement_status'),
            'notes' => $this->input('notes'),
        ];
    }
}
