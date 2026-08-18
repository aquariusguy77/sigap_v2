<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefugeeFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['Aktif', 'Verifikasi', 'Mutasi'])],
            'location' => ['nullable', 'string', 'max:100'],
            'document_status' => ['nullable', Rule::in(['Lengkap', 'Perlu Verifikasi', 'Belum Lengkap'])],
            'sort' => ['nullable', Rule::in(['name', 'internal_id', 'nationality', 'status', 'location', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in(['5', '10', '15', '20'])],
        ];
    }

    public function filters(): array
    {
        return [
            'keyword' => trim((string) $this->input('keyword')),
            'nationality' => (string) $this->input('nationality', ''),
            'status' => (string) $this->input('status', ''),
            'location' => (string) $this->input('location', ''),
            'document_status' => (string) $this->input('document_status', ''),
            'sort' => (string) $this->input('sort', 'name'),
            'direction' => (string) $this->input('direction', 'asc'),
            'per_page' => (int) $this->input('per_page', 10),
        ];
    }
}
