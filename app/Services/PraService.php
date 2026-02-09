<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PraLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PraService
{
    protected $baseUrl;
    protected $token;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('pra.api_url');
        $this->token = config('pra.api_token');
        $this->timeout = 30; // seconds
    }

    /**
     * Submit invoice to PRA
     */
    public function submitInvoice(Invoice $invoice)
    {
        // Check for duplicate submission
        if ($invoice->pra_status === 'success') {
            Log::info('PRA: Invoice already submitted successfully', ['invoice_id' => $invoice->id]);
            return [
                'success' => true,
                'message' => 'Invoice already submitted',
                'fiscal_number' => $invoice->pra_fiscal_code,
            ];
        }

        // Prepare payload according to PRA API specification
        $payload = $this->buildPayload($invoice);

        try {
            // Test mode - simulate successful response
            if (config('pra.test_mode', true)) {
                return $this->handleTestMode($invoice, $payload);
            }

            // Production mode - real API call
            return $this->submitToApi($invoice, $payload);

        } catch (\Exception $e) {
            return $this->handleError($invoice, $payload, $e);
        }
    }

    /**
     * Build PRA API payload from invoice
     */
    protected function buildPayload(Invoice $invoice): array
    {
        // Load relationships if not loaded
        $invoice->loadMissing(['branch', 'items.menuItem', 'customer']);

        $items = $invoice->items->map(function($item) {
            return [
                'item_code' => $item->menuItem->sku ?? 'ITEM-' . $item->menu_item_id,
                'item_name' => $item->menuItem->name ?? 'Unknown Item',
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->subtotal,
                'tax_amount' => (float) $item->tax_amount,
                'discount_amount' => (float) ($item->discount_amount ?? 0),
            ];
        })->toArray();

        return [
            'invoice_number' => $invoice->invoice_number,
            'local_invoice_number' => $invoice->local_invoice_number,
            'branch_code' => $invoice->branch->code ?? 'BR001',
            'branch_name' => $invoice->branch->name ?? 'Main Branch',
            'branch_ntn' => $invoice->branch->ntn ?? '',
            'branch_address' => $invoice->branch->address ?? '',
            
            'customer_name' => $invoice->customer->name ?? 'Walk-in Customer',
            'customer_phone' => $invoice->customer->phone ?? '',
            'customer_ntn' => $invoice->customer->ntn ?? '',
            
            'invoice_date' => $invoice->created_at->format('Y-m-d'),
            'invoice_time' => $invoice->created_at->format('H:i:s'),
            
            'items' => $items,
            'items_count' => count($items),
            
            'subtotal' => (float) $invoice->subtotal,
            'discount_amount' => (float) $invoice->discount_amount,
            'discount_type' => $invoice->discount_type,
            'tax_rate' => (float) $invoice->tax_rate,
            'tax_amount' => (float) $invoice->tax_amount,
            'service_charge' => (float) ($invoice->service_charge ?? 0),
            'total_amount' => (float) $invoice->total_amount,
            
            'payment_method' => $invoice->payments->first()->method ?? 'cash',
            'paid_at' => $invoice->paid_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Handle test mode (simulation)
     */
    protected function handleTestMode(Invoice $invoice, array $payload): array
    {
        // Simulate small delay like real API
        usleep(100000); // 100ms

        $fiscalNumber = 'PRA-' . date('Ymd') . '-' . strtoupper(uniqid());
        $qrCode = $this->generateTestQrCode($invoice, $fiscalNumber);

        $response = [
            'success' => true,
            'message' => 'Invoice registered successfully (TEST MODE)',
            'fiscal_number' => $fiscalNumber,
            'qr_code' => $qrCode,
            'submission_date' => now()->format('Y-m-d H:i:s'),
            'test_mode' => true,
        ];

        // Log the attempt
        $this->logSubmission($invoice, $payload, $response, 'success');

        // Update invoice
        $invoice->update([
            'pra_status' => 'success',
            'pra_invoice_number' => $fiscalNumber,
            'pra_fiscal_code' => $fiscalNumber,
            'pra_qr_code' => $qrCode,
            'pra_submitted_at' => now(),
            'pra_response' => json_encode($response),
        ]);

        Log::info('PRA: Test mode submission successful', [
            'invoice_id' => $invoice->id,
            'fiscal_number' => $fiscalNumber,
        ]);

        return $response;
    }

    /**
     * Submit to real PRA API
     */
    protected function submitToApi(Invoice $invoice, array $payload): array
    {
        // Mark as submitted to prevent duplicate submissions
        $invoice->update(['pra_status' => 'submitted']);

        $response = Http::withToken($this->token)
            ->timeout($this->timeout)
            ->retry(3, 1000) // Retry 3 times with 1 second delay
            ->post($this->baseUrl . '/invoices', $payload);

        $responseData = $response->json();
        $success = $response->successful() && ($responseData['success'] ?? false);
        $status = $success ? 'success' : 'failed';

        // Log the attempt
        $this->logSubmission($invoice, $payload, $responseData, $status);

        if ($success) {
            $invoice->update([
                'pra_status' => 'success',
                'pra_invoice_number' => $responseData['invoice_number'] ?? null,
                'pra_fiscal_code' => $responseData['fiscal_number'] ?? $responseData['fiscal_code'] ?? null,
                'pra_qr_code' => $responseData['qr_code'] ?? null,
                'pra_submitted_at' => now(),
                'pra_response' => json_encode($responseData),
            ]);

            Log::info('PRA: Submission successful', [
                'invoice_id' => $invoice->id,
                'fiscal_number' => $responseData['fiscal_number'] ?? null,
            ]);
        } else {
            $invoice->update([
                'pra_status' => 'failed',
                'pra_response' => json_encode($responseData),
            ]);

            Log::warning('PRA: Submission failed', [
                'invoice_id' => $invoice->id,
                'response' => $responseData,
            ]);
        }

        return array_merge($responseData, ['success' => $success]);
    }

    /**
     * Handle submission error
     */
    protected function handleError(Invoice $invoice, array $payload, \Exception $e): array
    {
        $errorResponse = [
            'success' => false,
            'message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'error_type' => get_class($e),
        ];

        // Log the error
        $this->logSubmission($invoice, $payload, $errorResponse, 'failed', $e->getMessage());

        // Update invoice status
        $invoice->update([
            'pra_status' => 'failed',
            'pra_response' => json_encode($errorResponse),
        ]);

        Log::error('PRA: Submission error', [
            'invoice_id' => $invoice->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return $errorResponse;
    }

    /**
     * Log PRA submission attempt
     */
    protected function logSubmission(Invoice $invoice, array $request, $response, string $status, ?string $errorMessage = null): void
    {
        PraLog::create([
            'invoice_id' => $invoice->id,
            'request_data' => $request,
            'response_data' => is_array($response) ? $response : ['raw' => $response],
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Generate test QR code data
     */
    protected function generateTestQrCode(Invoice $invoice, string $fiscalNumber): string
    {
        // Generate a QR code content similar to what PRA would return
        $qrData = [
            'fn' => $fiscalNumber,
            'dt' => now()->format('YmdHis'),
            'ta' => $invoice->total_amount,
            'tx' => $invoice->tax_amount,
            'br' => $invoice->branch->code ?? 'BR001',
        ];

        return base64_encode(json_encode($qrData));
    }

    /**
     * Verify invoice status with PRA
     */
    public function verifyInvoice(Invoice $invoice): array
    {
        if (!$invoice->pra_fiscal_code) {
            return [
                'success' => false,
                'message' => 'Invoice has no fiscal code',
            ];
        }

        if (config('pra.test_mode', true)) {
            return [
                'success' => true,
                'message' => 'Invoice verified (TEST MODE)',
                'status' => 'registered',
                'fiscal_number' => $invoice->pra_fiscal_code,
            ];
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout($this->timeout)
                ->get($this->baseUrl . '/invoices/' . $invoice->pra_fiscal_code);

            return array_merge($response->json(), [
                'success' => $response->successful(),
            ]);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
