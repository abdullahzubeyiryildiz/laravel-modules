<?php

namespace Modules\RolePermissionModule\Commands;

use Illuminate\Console\Command;
use Modules\RolePermissionModule\Services\RolePermissionService;

class SeedRolePermissionCommand extends Command
{
    protected $signature = 'role-permission:seed {--tenant= : Tenant ID}';

    protected $description = 'Varsayılan roller ve izinleri oluştur';

    public function handle(): int
    {
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;

        try {
            app(RolePermissionService::class)->seedDefaults($tenantId);
            $this->info('Varsayılan roller ve izinler başarıyla oluşturuldu.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Hata: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
