<?php

namespace App\Http\Requests;

use App\Firebase\Record;
use App\Firebase\RefugeeRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RefugeeUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'internal_id' => ['required', 'string', 'max:50', 'regex:/^RDS-\d{5}$/'],
            'name' => ['required', 'string', 'min:3', 'max:150', 'regex:/^[\pL\s\.\'\-]+$/u'],
            'nationality' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[\pL\s\.\'\-]+$/u'],
            'unhcr_number' => ['nullable', 'string', 'max:100', 'regex:/^[A-Z0-9\-\/]+$/'],
            'status' => ['required', Rule::in(['Aktif', 'Verifikasi', 'Mutasi'])],
            'location' => ['required', 'string', 'min:3', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'registered_at' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * ID internal harus unik. Pemeriksaan dilakukan terhadap Firebase, bukan
     * tabel database, karena aplikasi ini tidak memakai database relasional.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $internalId = (string) $this->input('internal_id');

            if ($internalId === '') {
                return;
            }

            $currentId = (string) $this->route('refugee');

            $bentrok = app(RefugeeRepository::class)->all()->first(
                fn (Record $item) => strtoupper((string) $item->internal_id) === strtoupper($internalId)
                    && (string) $item->id !== $currentId
            );

            if ($bentrok) {
                $validator->errors()->add('internal_id', 'ID internal ini sudah dipakai oleh data lain.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'internal_id' => strtoupper(trim((string) $this->input('internal_id'))),
            'name' => trim((string) $this->input('name')),
            'nationality' => trim((string) $this->input('nationality')),
            'unhcr_number' => strtoupper(trim((string) $this->input('unhcr_number'))),
            'location' => trim((string) $this->input('location')),
            'notes' => trim((string) $this->input('notes')),
        ]);
    }

    public function messages(): array
    {
        return [
            'internal_id.regex' => 'ID internal harus memakai format RDS-24001.',
            'name.regex' => 'Nama hanya boleh berisi huruf, spasi, titik, petik, dan tanda hubung.',
            'nationality.regex' => 'Kebangsaan hanya boleh berisi huruf, spasi, titik, petik, dan tanda hubung.',
            'unhcr_number.regex' => 'Nomor UNHCR hanya boleh berisi huruf kapital, angka, garis miring, dan tanda hubung.',
            'status.in' => 'Status data harus Aktif, Verifikasi, atau Mutasi.',
            'registered_at.before_or_equal' => 'Tanggal registrasi tidak boleh melewati hari ini.',
        ];
    }

    public function attributes(): array
    {
        return [
            'internal_id' => 'ID internal',
            'name' => 'nama lengkap',
            'nationality' => 'kebangsaan',
            'unhcr_number' => 'nomor UNHCR',
            'status' => 'status data',
            'location' => 'lokasi aktif',
            'notes' => 'catatan',
            'registered_at' => 'tanggal registrasi',
        ];
    }

    public function payload(): array
    {
        return [
            'internal_id' => (string) $this->input('internal_id'),
            'name' => (string) $this->input('name'),
            'nationality' => (string) $this->input('nationality'),
            'unhcr_number' => $this->input('unhcr_number'),
            'status' => (string) $this->input('status'),
            'location' => $this->input('location'),
            'notes' => $this->input('notes'),
            'registered_at' => $this->input('registered_at'),
        ];
    }
}
