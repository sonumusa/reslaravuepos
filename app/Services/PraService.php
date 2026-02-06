<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PraLog;
use Illuminate\Support\Facades\Http;

class PraService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = config('pra.api_url');
        $this->token = config('pra.api_token');
    }

    public function submitInvoice(Invoice $invoice)
    {
        $payload = [
            'invoice_number' => $invoice->invoice_number,
            'total_amount' => $invoice->total_amount,
            'tax_amount' => $invoice->tax_amount,
            'date' => now()->format('Y-m-d H:i:s'),
            'branch_code' => $invoice->branch->code ?? 'BR001',
        ];

        try {
            // Mock response for now if not configured
            if (config('pra.test_mode', true)) {
                $response = [
                    'success' => true,
                    'fiscal_number' => 'PRA-' . uniqid(),
                    'qr_code' => 'QR-' . uniqid(),
                ];
                $status = 'success';
            } else {
                $response = Http::withToken($this->token)->post($this->baseUrl . '/invoices', $payload)->json();
                $status = $response['success'] ? 'success' : 'failed';
            }

            // Log the attempt
            PraLog::create([
                'invoice_id' => $invoice->id,
                'request_data' => $payload,
                'response_data' => $response,
                'status' => $status,
            ]);

            if ($status === 'success') {
                $invoice->update([
                    'pra_status' => 'success',
                    'pra_fiscal_invoice_number' => $response['fiscal_number'] ?? null,
                    'pra_qr_code' => $response['qr_code'] ?? null,
                ]);
            } else {
                $invoice->update(['pra_status' => 'failed']);
            }

            return $response;

        } catch (\Exception $e) {
            PraLog::create([
                'invoice_id' => $invoice->id,
                'request_data' => $payload,
                'response_data' => ['error' => $e->getMessage()],
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $invoice->update(['pra_status' => 'failed']);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
