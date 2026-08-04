<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class RotateSettingsKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:rotate-key {oldKey : The old APP_KEY (can be base64:...)} {--dry-run : Preview changes without persisting updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate encrypted settings from an old APP_KEY to the current APP_KEY';

    public function handle()
    {
        $oldKey = $this->argument('oldKey');

        if (empty($oldKey)) {
            $this->error('oldKey argument is required');
            return 1;
        }

        try {
            // Normalize old key: support base64: prefix
            if (Str::startsWith($oldKey, 'base64:')) {
                $decoded = base64_decode(substr($oldKey, 7));
            } else {
                $decoded = $oldKey;
            }

            $cipher = config('app.cipher', 'AES-256-CBC');
            $oldEncrypter = new Encrypter($decoded, $cipher);
        } catch (\Throwable $e) {
            $this->error('Failed to create encrypter with provided old key: ' . $e->getMessage());
            return 1;
        }

        $this->info('Rotating encrypted settings...');
        $settings = Setting::where('type', 'encrypted')->get();
        $count = 0;
        $failed = 0;

        foreach ($settings as $s) {
            try {
                $plain = $oldEncrypter->decryptString($s->value);
                if ($this->option('dry-run')) {
                    $this->line("[dry-run] {$s->key}");
                    $count++;
                    continue;
                }

                $s->value = Crypt::encryptString($plain);
                $s->save();
                $count++;
            } catch (\Throwable $e) {
                $this->error("Failed to rotate setting {$s->key}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info($this->option('dry-run') ? 'Dry run completed.' : 'Completed.');
        $this->info("Rotated: {$count}, Failed: {$failed}");
        return 0;
    }
}
