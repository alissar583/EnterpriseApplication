<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\UserFile;
use Illuminate\Console\Command;

class CheckoutFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:checkout-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check out files after 10 h';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeout = now()->subMinutes(1)->format('Y-m-d H:i:s');
        $files = File::query()
            ->where('status', 1)
            ->where('updated_at', '<', $timeout)
            ->pluck('id')
            ->toArray();
        info($files);
        File::query()->whereIn('id', $files)->update(['status' => 0]);
        UserFile::query()->whereIn('file_id', $files)->update([
            'updated_at' => now()
        ]);
    }
}
