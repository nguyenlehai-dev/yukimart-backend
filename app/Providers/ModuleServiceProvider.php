<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Tự động load routes từ tất cả modules.
 *
 * Khi thêm module mới, chỉ cần tạo file routes.php
 * trong thư mục module → tự động được load.
 *
 * Cấu trúc:
 *   app/Modules/{TenModule}/routes.php
 */
class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleRoutes();
    }

    /**
     * Quét app/Modules/[Module]/routes.php và load tất cả.
     */
    private function loadModuleRoutes(): void
    {
        $modulesPath = app_path('Modules');

        if (!is_dir($modulesPath)) {
            return;
        }

        $modules = scandir($modulesPath);

        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }

            $routeFile = $modulesPath . '/' . $module . '/routes.php';

            if (file_exists($routeFile)) {
                Route::prefix('api')
                    ->middleware('api')
                    ->group($routeFile);
            }
        }
    }
}
