<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\PayerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The people who need to pay for one product: add them (CSV or one by one),
 * send their payment links in the background, and track them.
 */
class ProductPayerController extends Controller
{
    public function __construct(protected PayerService $payers)
    {
    }

    private function product(Request $request, int $id): Product
    {
        return Product::where('id', $id)->where('merchant_id', $request->user()->id)->firstOrFail();
    }

    public function import(Request $request, int $id): RedirectResponse
    {
        $product = $this->product($request, $id);

        $request->validate([
            'file'    => 'required|file|max:10240',
            'combine' => 'nullable|boolean',
        ]);

        try {
            $result = $this->payers->importCsv(
                $product,
                file_get_contents($request->file('file')->getRealPath()),
                $request->boolean('combine')
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('merchant.products.show', $product->id)->withErrors(['file' => $e->getMessage()]);
        }

        return redirect()->route('merchant.products.show', $product->id)
            ->with('success', $this->importMessage($result))
            ->with('payer_skipped', array_slice($result['skipped_rows'], 0, 10));
    }

    public function store(Request $request, int $id): RedirectResponse
    {
        $product = $this->product($request, $id);

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'student_name' => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'mobile'       => 'nullable|string|max:50',
            'amount'       => 'nullable|numeric|gt:0|max:1000000',
        ]);

        $validator->after(function ($v) use ($request) {
            if (!$request->filled('email') && !$request->filled('mobile')) {
                $v->errors()->add('email', 'Add an email address or a mobile number.');
            }
        });

        if ($validator->fails()) {
            return redirect()->route('merchant.products.show', $product->id)
                ->withErrors($validator, 'addPayer')->withInput();
        }

        $result = $this->payers->addOne($product, $validator->validated());

        return redirect()->route('merchant.products.show', $product->id)->with('success', $this->importMessage($result));
    }

    public function send(Request $request, int $id): RedirectResponse
    {
        $product = $this->product($request, $id);

        if (!$request->user()->isOnboarded()) {
            return redirect()->back()->with('error', 'Payment links can be sent once Edfundo has approved your account. Finish your set-up and we\'ll review it as soon as possible.');
        }

        $queued = $this->payers->queueLinks($product);

        return $this->queuedResponse($product, $queued);
    }

    public function retry(Request $request, int $id): RedirectResponse
    {
        $product = $this->product($request, $id);

        if (!$request->user()->isOnboarded()) {
            return redirect()->back()->with('error', 'Payment links can be sent once Edfundo has approved your account. Finish your set-up and we\'ll review it as soon as possible.');
        }

        $queued = $this->payers->queueLinks($product, ['failed']);

        return $this->queuedResponse($product, $queued);
    }

    public function remove(Request $request, int $id, int $invoiceId): RedirectResponse
    {
        $product = $this->product($request, $id);

        if (!$this->payers->remove($product, $invoiceId)) {
            return redirect()->route('merchant.products.show', $product->id)
                ->with('error', 'That payer has already paid or has been removed, so it can\'t be removed.');
        }

        return redirect()->route('merchant.products.show', $product->id)->with('success', 'Payer removed from this product.');
    }

    public function export(Request $request, int $id): StreamedResponse
    {
        $product = $this->product($request, $id);

        return response()->streamDownload(function () use ($product) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM so Excel reads it correctly
            fputcsv($out, ['Parent', 'Children', 'Email', 'Mobile', 'Amount (AED)', 'Payment', 'Link emailed', 'Link emailed at', 'Payment link']);

            $this->payers->payersQuery($product)
                ->with(['consumer', 'invoiceDetails.consumer'])
                ->chunkById(200, function ($invoices) use ($out) {
                    foreach ($invoices as $invoice) {
                        $children = $invoice->invoiceDetails
                            ->map(fn ($line) => $line->consumer?->student_name ?: $line->consumer?->name)
                            ->filter()->unique()->implode(', ');

                        fputcsv($out, [
                            $invoice->consumer?->name,
                            $children,
                            $invoice->consumer?->email,
                            $invoice->consumer?->mobile_number,
                            number_format($invoice->total_fee, 2, '.', ''),
                            $invoice->status->label(),
                            $invoice->link_email_status ?? 'sent',
                            $invoice->link_emailed_at?->format('Y-m-d H:i'),
                            route('public.invoice.show', $invoice->uuid),
                        ]);
                    }
                });

            fclose($out);
        }, 'payers_' . $product->id . '_' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function template(Request $request, int $id): StreamedResponse
    {
        $this->product($request, $id);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Parent Name', 'Student/Player Name', 'Parent Email', 'Parent Mobile', 'Amount']);
            fputcsv($out, ['Chris Daniels', 'Henry Daniels', 'chris@example.com', '+971501234567', '14500']);
            fputcsv($out, ['Chris Daniels', 'Poppy Daniels', 'chris@example.com', '+971501234567', '13050']);
            fputcsv($out, ['Sam Jones', 'Ella Jones', 'sam@example.com', '+971507654321', '']);
            fclose($out);
        }, 'payers_template.csv', ['Content-Type' => 'text/csv']);
    }

    private function queuedResponse(Product $product, int $queued): RedirectResponse
    {
        if ($queued === 0) {
            return redirect()->route('merchant.products.show', $product->id)
                ->with('error', 'There is nobody left to send to. Everyone with an email address has already been sent their link.');
        }

        $minutes = (int) ceil($queued / max(1, (int) config('invoices.send_batch_size', 100)));

        return redirect()->route('merchant.products.show', $product->id)->with(
            'success',
            "{$queued} payment " . ($queued === 1 ? 'link' : 'links') . " queued. They're sent in the background, about "
            . (int) config('invoices.send_batch_size', 100) . " a minute"
            . ($minutes > 1 ? ", so allow roughly {$minutes} minutes" : '') . '. Refresh this page to watch the status.'
        );
    }

    /** @param array<string, mixed> $r */
    private function importMessage(array $r): string
    {
        if ($r['children'] === 0 && $r['already'] > 0 && $r['skipped'] === 0) {
            return 'Everyone in that file is already on this product, so nothing was added.';
        }

        $parts = [];
        $parts[] = $r['children'] === $r['invoices']
            ? "{$r['children']} " . ($r['children'] === 1 ? 'payer' : 'payers') . ' added'
            : "{$r['children']} children added as {$r['invoices']} " . ($r['invoices'] === 1 ? 'invoice' : 'invoices') . ' (families combined)';

        if (($r['custom'] ?? 0) > 0) {
            $parts[] = "{$r['custom']} with their own amount from the file";
        }
        if ($r['already'] > 0) {
            $parts[] = "{$r['already']} already on this product";
        }
        if ($r['skipped'] > 0) {
            $parts[] = "{$r['skipped']} " . ($r['skipped'] === 1 ? 'row' : 'rows') . ' skipped';
        }

        return implode(', ', $parts) . '. Nothing has been emailed yet — check the list, then click "Send payment links".';
    }
}
