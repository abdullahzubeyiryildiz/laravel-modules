<?php

namespace Modules\RbacModule\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantExportService
{
    /**
     * Tenant'ı export et (SQL dump + files manifest)
     */
    public function export(int $tenantId, ?string $format = 'json'): array
    {
        $tenant = Tenant::findOrFail($tenantId);
        
        $export = [
            'tenant' => $tenant->toArray(),
            'exported_at' => now()->toIso8601String(),
            'format_version' => '1.0',
        ];

        // Veritabanı verilerini export et
        $export['data'] = $this->exportDatabaseData($tenantId);

        // Dosya manifest'i oluştur
        $export['files_manifest'] = $this->exportFilesManifest($tenantId);

        // Format'a göre kaydet
        if ($format === 'json') {
            return $export;
        }

        // SQL format
        if ($format === 'sql') {
            return $this->exportToSql($tenantId, $export);
        }

        return $export;
    }

    /**
     * Veritabanı verilerini export et
     */
    protected function exportDatabaseData(int $tenantId): array
    {
        $tables = [
            'users',
            'tenant_users',
            'roles',
            'permissions',
            'role_permissions',
            'audit_logs',
            'files',
            // Diğer tenant bazlı tablolar buraya eklenebilir
        ];

        $data = [];

        foreach ($tables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                $data[$table] = DB::table($table)
                    ->where('tenant_id', $tenantId)
                    ->get()
                    ->toArray();
            }
        }

        return $data;
    }

    /**
     * Dosya manifest'i oluştur
     */
    protected function exportFilesManifest(int $tenantId): array
    {
        if (!class_exists(\Modules\FileManagerModule\Models\File::class)) {
            return [];
        }

        $files = \Modules\FileManagerModule\Models\File::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        return $files->map(function ($file) {
            return [
                'id' => $file->id,
                'path' => $file->path,
                'original_name' => $file->original_name,
                'size_bytes' => $file->size_bytes,
                'mime_type' => $file->mime_type,
                'hash_sha256' => $file->hash_sha256,
                'disk' => $file->disk,
                'bucket' => $file->bucket,
            ];
        })->toArray();
    }

    /**
     * SQL format'a export et
     */
    protected function exportToSql(int $tenantId, array $export): string
    {
        $sql = "-- Tenant Export: {$export['tenant']['name']}\n";
        $sql .= "-- Exported at: {$export['exported_at']}\n\n";

        foreach ($export['data'] as $table => $rows) {
            if (empty($rows)) {
                continue;
            }

            $sql .= "-- Table: {$table}\n";
            foreach ($rows as $row) {
                $row = (array) $row;
                $columns = implode(', ', array_keys($row));
                $values = implode(', ', array_map(function ($value) {
                    return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
                }, array_values($row)));
                
                $sql .= "INSERT INTO {$table} ({$columns}) VALUES ({$values});\n";
            }
            $sql .= "\n";
        }

        return $sql;
    }

    /**
     * Export'u dosyaya kaydet
     */
    public function exportToFile(int $tenantId, ?string $format = 'json'): string
    {
        $export = $this->export($tenantId, $format);
        $tenant = Tenant::findOrFail($tenantId);
        
        $fileName = "tenant_export_{$tenant->slug}_" . now()->format('Y-m-d_His') . '.' . $format;
        $filePath = "exports/{$fileName}";

        if ($format === 'json') {
            Storage::disk('local')->put($filePath, json_encode($export, JSON_PRETTY_PRINT));
        } else {
            Storage::disk('local')->put($filePath, $export);
        }

        return $filePath;
    }

    /**
     * Tenant'ı import et
     */
    public function import(array $exportData, ?int $newTenantId = null): Tenant
    {
        DB::beginTransaction();

        try {
            // Yeni tenant oluştur veya mevcut tenant'ı kullan
            if ($newTenantId) {
                $tenant = Tenant::findOrFail($newTenantId);
            } else {
                $tenant = Tenant::create([
                    'name' => $exportData['tenant']['name'] . ' (Imported)',
                    'slug' => $exportData['tenant']['slug'] . '-imported-' . Str::random(6),
                    'email' => $exportData['tenant']['email'] ?? null,
                    'is_active' => true,
                ]);
            }

            // Verileri import et
            $this->importDatabaseData($exportData['data'] ?? [], $tenant->id);

            DB::commit();

            return $tenant;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Veritabanı verilerini import et
     */
    protected function importDatabaseData(array $data, int $newTenantId): void
    {
        foreach ($data as $table => $rows) {
            if (empty($rows) || !\Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            foreach ($rows as $row) {
                $row = (array) $row;
                
                // tenant_id'yi güncelle
                $row['tenant_id'] = $newTenantId;
                
                // id'yi kaldır (yeni ID oluşturulacak)
                unset($row['id']);
                
                // created_at/updated_at'i güncelle
                if (isset($row['created_at'])) {
                    $row['created_at'] = now();
                }
                if (isset($row['updated_at'])) {
                    $row['updated_at'] = now();
                }

                DB::table($table)->insert($row);
            }
        }
    }
}
