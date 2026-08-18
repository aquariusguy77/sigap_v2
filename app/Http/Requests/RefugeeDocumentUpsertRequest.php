<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefugeeDocumentUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refugee_id' => ['required', 'string', 'max:120'],
            'document_type' => ['required', 'string', 'max:100'],
            'file_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._\-]+$/'],
            'file_path' => ['nullable', 'string', 'max:255'],
            'drive_file_id' => ['nullable', 'string', 'max:255'],
            'verification_status' => ['required', Rule::in(['Lengkap', 'Perlu Verifikasi', 'Belum Lengkap'])],
            'uploaded_at' => ['required', 'date', 'before_or_equal:today'],
            'uploaded_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'file_name' => trim((string) $this->input('file_name')),
            'file_path' => trim((string) $this->input('file_path')),
            'drive_file_id' => trim((string) $this->input('drive_file_id')),
            'notes' => trim((string) $this->input('notes')),
        ]);
    }

    public function messages(): array
    {
        return [
            'file_name.regex' => 'Nama file hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung.',
            'verification_status.in' => 'Status verifikasi harus Lengkap, Perlu Verifikasi, atau Belum Lengkap.',
            'uploaded_at.required' => 'Tanggal unggah wajib diisi.',
            'uploaded_at.before_or_equal' => 'Tanggal unggah tidak boleh melewati hari ini.',
            'uploaded_file.mimes' => 'File unggahan harus berupa PDF, JPG, JPEG, atau PNG.',
            'uploaded_file.max' => 'Ukuran file unggahan maksimal 5 MB.',
        ];
    }

    public function payload(): array
    {
        return [
            'refugee_id' => (string) $this->input('refugee_id'),
            'document_type' => (string) $this->input('document_type'),
            'file_name' => (string) $this->input('file_name'),
            'file_path' => $this->input('file_path'),
            'drive_file_id' => $this->input('drive_file_id'),
            'verification_status' => (string) $this->input('verification_status'),
            'uploaded_at' => $this->input('uploaded_at'),
            'notes' => $this->input('notes'),
        ];
    }
}
