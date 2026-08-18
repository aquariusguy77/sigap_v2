<?php

namespace App\Services;

class FirebaseService
{
    public function appConfig(): array
    {
        return [
            'auth' => [
                'login_mode' => config('sigap.auth.login_mode', 'hybrid'),
                'demo_enabled' => (bool) config('sigap.auth.demo_enabled', true),
                'laravel_auth_enabled' => (bool) config('sigap.auth.laravel_auth_enabled', true),
            ],
            'data' => [
                'sample_data_enabled' => (bool) config('sigap.data.sample_data_enabled', true),
                'firebase_read_enabled' => (bool) config('sigap.data.firebase_read_enabled', true),
            ],
        ];
    }

    public function config(): array
    {
        $firebase = config('sigap.firebase', []);

        return [
            'app' => $this->appConfig(),
            'firebase' => [
                'database_url' => $firebase['database_url'] ?? 'https://ralf-803d6-default-rtdb.asia-southeast1.firebasedatabase.app/',
                'project_id' => $firebase['project_id'] ?? 'ralf-803d6',
                'api_key' => $firebase['api_key'] ?? null,
                'auth_domain' => $firebase['auth_domain'] ?? 'ralf-803d6.firebaseapp.com',
                'storage_bucket' => $firebase['storage_bucket'] ?? 'ralf-803d6.firebasestorage.app',
                'storage_disk' => $firebase['storage_disk'] ?? 'local',
                'storage_prefix' => $firebase['storage_prefix'] ?? 'documents',
                'storage_public_base_url' => $firebase['storage_public_base_url'] ?? null,
                'storage_bearer_token' => $firebase['storage_bearer_token'] ?? null,
                'database_secret' => $firebase['database_secret'] ?? null,
                'paths' => $firebase['paths'] ?? [
                    'refugees' => '/refugees',
                    'documents' => '/documents',
                    'placements' => '/placements',
                    'audit_trails' => '/audit_trails',
                    'reports' => '/reports',
                    'users' => '/users',
                ],
                'node_map' => [
                    'refugees' => [
                        'path' => '/refugees',
                        'fields' => ['internal_id', 'name', 'nationality', 'unhcr_number', 'status', 'location', 'document_status', 'notes', 'registered_at', 'updated_at'],
                    ],
                    'documents' => [
                        'path' => '/documents',
                        'fields' => ['refugee_id', 'document_type', 'file_name', 'file_path', 'firebase_document_key', 'verification_status', 'uploaded_at', 'uploaded_by', 'notes'],
                    ],
                    'placements' => [
                        'path' => '/placements',
                        'fields' => ['refugee_id', 'location_name', 'entered_at', 'exited_at', 'placement_status', 'notes'],
                    ],
                    'audit_trails' => [
                        'path' => '/audit_trails',
                        'fields' => ['refugee_id', 'field_name', 'old_value', 'new_value', 'action_label', 'performed_by_name', 'reason', 'performed_at'],
                    ],
                    'reports' => [
                        'path' => '/reports',
                        'fields' => ['name', 'note', 'filters', 'downloaded_at', 'downloaded_by'],
                    ],
                    'users' => [
                        'path' => '/users',
                        'fields' => ['name', 'role', 'email', 'status'],
                    ],
                ],
            ],
        ];
    }
}
