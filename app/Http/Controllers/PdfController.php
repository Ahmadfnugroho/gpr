<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function __invoke(Transaction $order)
    {
        try {
            // Eager load semua relasi yang diperlukan untuk PDF dengan nested relationships
            $record = Transaction::with([
                'user',
                'promo',
                'detailTransactions.product.rentalIncludes.includedProduct',
                'detailTransactions.bundling.products.rentalIncludes.includedProduct',
                'detailTransactions.bundling.products.items',
                'detailTransactions.productItems'
            ])->findOrFail($order->id);

            // Validasi data penting tidak null
            if (!$record->user) {
                abort(404, 'User data not found for this transaction');
            }

            if ($record->detailTransactions->isEmpty()) {
                abort(404, 'Transaction details not found');
            }

            // Log warning for missing product/bundling data but continue processing
            foreach ($record->detailTransactions as $detail) {
                if (!$detail->bundling_id && !$detail->product) {
                    \Log::warning('Detail transaction without product or bundling data', [
                        'transaction_id' => $record->id,
                        'detail_id' => $detail->id ?? 'unknown',
                        'product_id' => $detail->product_id ?? 'null',
                        'bundling_id' => $detail->bundling_id ?? 'null'
                    ]);
                }
                
                if ($detail->bundling_id && !$detail->bundling) {
                    \Log::warning('Detail transaction with missing bundling relationship', [
                        'transaction_id' => $record->id,
                        'detail_id' => $detail->id ?? 'unknown',
                        'bundling_id' => $detail->bundling_id
                    ]);
                }
            }

            // return view('pdf', ['record' => $record]);

            // Get current authenticated user (staff who prints the invoice)
            $currentUser = auth()->user();
            
            // Log current user info for debugging
            \Log::info('PDF Generation - Current User Info', [
                'user_id' => $currentUser?->id,
                'user_name' => $currentUser?->name,
                'user_email' => $currentUser?->email,
                'transaction_id' => $record->id
            ]);
            
            return Pdf::loadView('pdf', [
                'record' => $record,
                'currentUser' => $currentUser, // Pass current user data to the PDF view
                'staffName' => $currentUser?->name ?? 'Staff GPR' // Fallback name
            ])
                ->stream('order.pdf')
                // ->download($record->booking_transaction_id . '.pdf')
                ;
                
        } catch (\Exception $e) {
            // Log error untuk debugging
            \Log::error('PDF Generation Error: ' . $e->getMessage(), [
                'transaction_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            abort(500, 'Error generating PDF: ' . $e->getMessage());
        }
    }
}
