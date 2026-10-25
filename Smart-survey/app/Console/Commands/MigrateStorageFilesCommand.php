<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Survey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateStorageFilesCommand extends Command
{
    protected $signature = 'storage:migrate-private';
    protected $description = 'Move deeds and payment slips from public storage to private storage';

    public function handle(): int
    {
        $moved = 0;

        // Move deeds
        $surveys = Survey::whereNotNull('deed_path')->get();
        foreach ($surveys as $survey) {
            $path = $survey->deed_path;
            if (Storage::disk('public')->exists($path)) {
                $content = Storage::disk('public')->get($path);
                Storage::disk('private')->put($path, $content);
                Storage::disk('public')->delete($path);
                $moved++;
            }
        }

        // Move payment slips
        $payments = Payment::whereNotNull('payment_slip_path')->get();
        foreach ($payments as $payment) {
            $path = $payment->payment_slip_path;
            if (Storage::disk('public')->exists($path)) {
                $content = Storage::disk('public')->get($path);
                Storage::disk('private')->put($path, $content);
                Storage::disk('public')->delete($path);
                $moved++;
            }
        }

        $this->info("Moved {$moved} files from public storage to secure private storage.");
        return self::SUCCESS;
    }
}
