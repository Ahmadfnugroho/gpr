<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Models\Product;
use App\Models\Transaction;

class TransactionController extends Controller
{
    public function store(TransactionRequest $request)
    {
        // Ambil data yang sudah tervalidasi
        // Mengambil data yang sudah tervalidasi
        $validatedData = $request->validated();

        // Cari produk berdasarkan ID
        $product = Product::find($validatedData['product_id']);

        // Pastikan produk ditemukan
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Set status transaksi dan booking transaction ID menggunakan Transaction model
        $validatedData['status'] = 'booking';

        // Panggil generateUniqueBookingTrxId() dari model Transaction
        $transaction = new Transaction($validatedData);
        $validatedData['booking_transaction_id'] = $transaction->generateUniqueBookingTrxId();

        // Cek apakah 'duration' adalah angka valid
        $duration = (int) $validatedData['duration']; // Pastikan menjadi integer
        if ($duration <= 0) {
            return response()->json(['error' => 'Invalid duration'], 400); // Jika duration tidak valid, kembalikan error
        }

        // Set durasi dan tanggal selesai
        $startDate = new \DateTime($validatedData['started_at']);
        $interval = new \DateInterval('P' . $duration . 'D'); // Format yang benar: P{X}D
        $endDate = $startDate->add($interval);
        $validatedData['end_date'] = $endDate->format('Y-m-d'); // Format tanggal yang benar

        // Simpan transaksi baru
        $transaction = Transaction::create($validatedData);

        // Kembalikan response JSON jika berhasil
        return response()->json($transaction, 201); // Contoh response JSON
    }
}
