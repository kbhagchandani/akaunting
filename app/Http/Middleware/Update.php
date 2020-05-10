<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;
use File;

class Update
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (File::isFile(base_path('Akaunting_2.0.9-Stable.zip'))) {
            info('Update Middleware Start...');

            // Unzip the file
            $zip = new ZipArchive();

            // Delete Vendor
            File::deleteDirectory(base_path('vendor'));

            info('Delete Vendor');

            $temp_path = base_path();

            $file = base_path('Akaunting_2.0.9-Stable.zip');

            info('Akaunting_2.0.9-Stable.zip start unzip...');

            if (($zip->open($file) !== true) || !$zip->extractTo($temp_path)) {
                info('Zipi çıkaramadııı');

                return $next($request);
            }

            $zip->close();

            info('Akaunting_2.0.9-Stable.zip finish unzip.');

            // Delete zip file
            File::delete($file);

            info('Delete Akaunting_2.0.9-Stable.zip file.');

            info('Update Middleware Finish.');

            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                $url = "https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
            } else {
                $url = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
            }

            Artisan::call('cache:clear');

            info('cache:clear Finish.');

            Artisan::call('migrate', ['--force' => true]);

            info('migrate Finish.');

            header("Location: " . $url); 
            exit();
        }

        return $next($request);
    }
}
