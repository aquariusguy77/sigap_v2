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
        return [
            'refugee_id' => ['required', 'string', 'max:120'],
            'location_name' => ['required', 'string', 'min:3', 'max:120'],
            'entered_at' => ['required', 'date', 'before_or_equal:today'],
            'exited_at' => ['nullable', 'date', 'after_or_equal:entered_at'],
            'placement_status' => ['required', Rule::in(['Aktif', 'Mutasi', 'Selesai', 'Transit'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'location_name' => trim((string) $this->input('location_name')),
            'notes' => trim((string) $this->input('notes')),
        ]);
    }

    public function messages(): array
    {
        return [
            'entered_at.required' => 'Tanggal masuk wajib diisi.',
            'entered_at.before_or_equal' => 'Tanggal masuk tidak boleh melewati hari ini.',
            'exited_at.after_or_equal' => 'Tanggal keluar harus sama atau setelah tanggal masuk.',
            'placement_status.in' => 'Status penempatan harus Aktif, Mutasi, Selesai, atau Transit.',
        ];
    }

    public function payload(): array
    {
        return [
            'refugee_id' => (string) $this->input('refugee_id'),
            'location_name' => (string) $this->input('location_name'),
            'entered_at' => $this->input('entered_at'),
            'exited_at' => $this->input('exited_at'),
            'placement_status' => (string) $this->input('placement_status'),
            'notes' => $this->input('notes'),
        ];
    }
}
