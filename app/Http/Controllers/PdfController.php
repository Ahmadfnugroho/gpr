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
                }

                if ($detail->bundling_id && !$detail->bundling) {
                }
            }

            // return view('pdf', ['record' => $record]);

            // Get current authenticated user (staff who prints the invoice)
            $currentUser = auth()->user();
            
            // Determine staff name with proper null checking
            $staffName = 'Staff GPR'; // Default fallback
            if ($currentUser && $currentUser->name) {
                $staffName = $currentUser->name;
            }

            return Pdf::loadView('pdf', [
                'record' => $record,
                'currentUser' => $currentUser, // Pass current user data to the PDF view
                'staffName' => $staffName // Staff name with proper fallback
            ])
                ->stream('order.pdf')
                // ->download($record->booking_transaction_id . '.pdf')
            ;
        } catch (\Exception $e) {
            // Log error untuk debugging

            abort(500, 'Error generating PDF: ' . $e->getMessage());
        }
    }
}
