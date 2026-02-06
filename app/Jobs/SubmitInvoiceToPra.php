<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\PraService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubmitInvoiceToPra implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    protected $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function handle(PraService $praService)
    {
        if ($this->invoice->pra_status === 'success') {
            return;
        }

        $praService->submitInvoice($this->invoice);
    }
}
