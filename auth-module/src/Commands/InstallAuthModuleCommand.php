<?php

namespace Modules\AuthModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallAuthModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth-module:install {--quiet : Run in quiet mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Auth Module and configure User model';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Quiet mode kontrolü (composer script'lerinden çağrıldığında)
        $quiet = $this->option('quiet');

        if (!$quiet) {
            $this->info('Installing Auth Module...');
        }

        // User model'ini güncelle
        $result = $this->updateUserModel($quiet);

        if (!$quiet) {
            if ($result) {
                $this->info('Auth Module installed successfully!');
                $this->info('User model has been updated with HasSocialAccounts and HasTenantAndRole traits.');
            } else {
                $this->info('User model already has all required traits.');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * User model'ini güncelle
     */
    protected function updateUserModel(bool $quiet = false): bool
    {
        $userModelPath = app_path('Models/User.php');

        if (!File::exists($userModelPath)) {
            if (!$quiet) {
                $this->warn('User model not found at: ' . $userModelPath);
                $this->info('Please manually add traits to your User model.');
            }
            return false;
        }

        $content = File::get($userModelPath);
        $updated = false;

        // HasSocialAccounts trait'i ekle
        if (!str_contains($content, 'HasSocialAccounts')) {
            // Use statement ekle
            if (!str_contains($content, 'use Modules\\AuthModule\\Traits\\HasSocialAccounts;')) {
                $content = preg_replace(
                    '/(namespace App\\Models;)/',
                    "$1\n\nuse Modules\\AuthModule\\Traits\\HasSocialAccounts;",
                    $content,
                    1
                );
            }

            // Trait'i class'a ekle
            $content = preg_replace(
                '/(use HasFactory, Notifiable;)/',
                "$1\n    use HasSocialAccounts;",
                $content,
                1
            );
            $updated = true;
        }

        // HasTenantAndRole trait'i ekle
        if (!str_contains($content, 'HasTenantAndRole')) {
            // Use statement ekle
            if (!str_contains($content, 'use Modules\\AuthModule\\Traits\\HasTenantAndRole;')) {
                $content = preg_replace(
                    '/(namespace App\\Models;)/',
                    "$1\n\nuse Modules\\AuthModule\\Traits\\HasTenantAndRole;",
                    $content,
                    1
                );
            }

            // Trait'i class'a ekle (HasSocialAccounts'tan sonra)
            if (str_contains($content, 'use HasSocialAccounts')) {
                $content = preg_replace(
                    '/(use HasSocialAccounts;)/',
                    "$1\n    use HasTenantAndRole;",
                    $content,
                    1
                );
            } else {
                // HasSocialAccounts yoksa HasFactory'den sonra ekle
                $content = preg_replace(
                    '/(use HasFactory, Notifiable;)/',
                    "$1\n    use HasTenantAndRole;",
                    $content,
                    1
                );
            }
            $updated = true;
        }

        // Eski manuel method'ları kaldır (tenant, isAdmin, isManager, socialAccounts)
        $patterns = [
            '/\s*\/\*\*[\s\S]*?\*\/\s*public function tenant\(\)[\s\S]*?\n\s*\}\s*\n/',
            '/\s*\/\*\*[\s\S]*?\*\/\s*public function isAdmin\(\)[\s\S]*?\n\s*\}\s*\n/',
            '/\s*\/\*\*[\s\S]*?\*\/\s*public function isManager\(\)[\s\S]*?\n\s*\}\s*\n/',
            '/\s*\/\*\*[\s\S]*?\*\/\s*public function socialAccounts\(\)[\s\S]*?\n\s*\}\s*\n/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '', $content);
                $updated = true;
            }
        }

        if ($updated) {
            File::put($userModelPath, $content);
            if (!$quiet) {
                $this->info('User model updated successfully!');
                $this->info('Added traits: HasSocialAccounts, HasTenantAndRole');
            }
            return true;
        } else {
            if (!$quiet) {
                $this->info('User model already has all required traits.');
            }
            return false;
        }
    }
}
