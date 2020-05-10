<?php

namespace App\Console\Commands;

use App\Utilities\Console;
use App\Utilities\Updater;
use App\Utilities\Info;
use App\Utilities\Versions;
use App\Traits\SiteApi;
use File;
use Illuminate\Console\Command;

use App\Events\Install\UpdateFinished;

class UpdateCore extends Command
{
    use SiteApi;

    const CMD_SUCCESS = 0;

    const CMD_ERROR = 1;
    
    public $company_id;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:core';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allows to update Akaunting and modules directly through CLI';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        set_time_limit(3600); // 1 hour
        
        $this->company_id = 1;

        session(['company_id' => $this->company_id]);
        setting()->setExtraColumns(['company_id' => $this->company_id]);

        if (!$path = $this->download()) {
            return self::CMD_ERROR;
        }

        if (!$this->unzip($path)) {
            return self::CMD_ERROR;
        }

        if (!$this->copyFiles($path)) {
            return self::CMD_ERROR;
        }

        if (!$this->finish()) {
            return self::CMD_ERROR;
        }

        return self::CMD_SUCCESS;
    }

    public function download()
    {
        $this->info('Downloading update...');

        $file = null;
        $path = null;

        // Check core first
        $info = Info::all();

        $url = 'core/download/2.0.9/' . $info['php'] . '/' . $info['mysql'];

        if (!$response = static::getResponse('GET', $url, ['timeout' => 50, 'track_redirects' => true])) {
            throw new \Exception(trans('modules.errors.download', ['module' => 'core']));
        }

        $file = $response->getBody()->getContents();

        $path = 'temp-' . md5(mt_rand());
        $temp_path = storage_path('app/temp') . '/' . $path;

        $file_path = $temp_path . '/upload.zip';

        // Create tmp directory
        if (!File::isDirectory($temp_path)) {
            File::makeDirectory($temp_path);
        }

        // Add content to the Zip file
        $uploaded = is_int(file_put_contents($file_path, $file)) ? true : false;

        if (!$uploaded) {
            throw new \Exception(trans('modules.errors.zip', ['module' => 'core']));

            return false;
        }

        return $path;
    }

    public function unzip($path)
    {
        $this->info('Unzipping update...');

        try {
            Updater::unzip($path, 'core', '2.0.9', '2.0.6');
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return false;
        }

        return true;
    }

    public function copyFiles($path)
    {
        $this->info('Copying update files...');

        // Delete temp directory
        File::deleteDirectory(base_path('vendor'));

        try {
            Updater::copyFiles($path, 'core', '2.0.9', '2.0.6');
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return false;
        }

        return true;
    }

    public function finish()
    {
        set_time_limit(3600); // 1 hour

        $this->info('Finishing update...');

        //$this->call('cache:clear');
        
        $alias = 'core';
        $company = 1;
        $new = '2.0.9';
        $old = '2.0.6';

        try {
            $command = "update:finish {$alias} {$company} {$new} {$old}";

            if (true !== $result = Console::run($command)) {
                $message = !empty($result) ? $result : trans('modules.errors.finish', ['module' => 'core']);

                throw new \Exception($message);
            }
            /*
            session(['company_id' => $this->company_id]);
            setting()->setExtraColumns(['company_id' => $this->company_id]);

            // Disable model cache during update
            config(['laravel-model-caching.enabled' => false]);

            event(new UpdateFinished('core', '2.0.9', '2.0.6'));
            */
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return false;
        }

        return true;
    }
}
