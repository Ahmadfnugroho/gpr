<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Notifications\TransactionNotification;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    public function __construct(protected FonnteService $fonnteService) {}

    public function created(Transaction $transaction)
    {
        $this->sendTransactionNotification($transaction, 'created');
    }

    public function updated(Transaction $transaction)
    {
        if ($transaction->wasChanged('booking_status')) {
            $this->sendStatusChangeNotification($transaction);
        } else {
            $this->sendTransactionNotification($transaction, 'updated');
        }
    }

    protected function sendTransactionNotification(Transaction $transaction, string $eventType)
    {
        // Load semua relasi yang diperlukan
        $transaction->load([
            'user.userPhoneNumbers',
            'detailTransactions.product.rentalIncludes.includedProduct',
            'detailTransactions.bundling.products.rentalIncludes.includedProduct',
            'promo'
        ]);

        Log::info('Detail transaksi setelah load:', $transaction->toArray());


        $user = $transaction->user;
        $phone = $user->userPhoneNumbers->first()?->phone_number;

        if (!$phone) {
            return;
        }

        if ($user->email) {
            $user->notify(new TransactionNotification($transaction, $eventType));
        }

        $message = $this->buildTransactionMessage($transaction, $eventType);
        $this->fonnteService->sendMessage($phone, $message);
    }

    protected function sendStatusChangeNotification(Transaction $transaction)
    {
        $transaction->load([
            'user.userPhoneNumbers',
            'detailTransactions.product.rentalIncludes.includedProduct',
            'detailTransactions.bundling.products.rentalIncludes.includedProduct',
            'promo'
        ]);

        Log::info('Detail transaksi setelah load:', $transaction->toArray());


        $user = $transaction->user;
        $phone = $user->userPhoneNumbers->first()?->phone_number;

        if (!$phone) {
            return;
        }

        if ($user->email) {
            $user->notify(new TransactionNotification($transaction, 'updated'));
        }

        $message = $this->buildStatusChangeMessage($transaction);
        $this->fonnteService->sendMessage($phone, $message);
    }

    protected function buildTransactionMessage(Transaction $transaction, string $eventType): string
    {
        $userName = $transaction->user->name;
        $transactionId = $transaction->booking_transaction_id;
        $status = $transaction->booking_status;

        $verb = $eventType === 'created' ? 'dibuat' : 'diupdate';

        $message = "Hai $userName! 👋\n";
        $message .= "Transaksi sewa kamu (No: $transactionId) berhasil $verb dengan status: *$status*.\n\n";
        $message .= "📝 *Detail Transaksi:*\n";
        $message .= "- Tanggal Sewa: " . $transaction->start_date->format('d M Y') . " s.d. " . $transaction->end_date->format('d M Y') . "\n";
        $message .= "- Total: Rp " . number_format($transaction->grand_total, 0, ',', '.') . "\n";

        if ($transaction->promo_id && $transaction->promo) {
            $message .= "- Promo: " . $transaction->promo->name . "\n";
        }

        if ($transaction->note) {
            $message .= "- Catatan: " . $transaction->note . "\n";
        }

        if ($status === 'pending') {
            $message .= "\n💰 DP: Rp " . number_format($transaction->down_payment, 0, ',', '.') .
                "\nSisa pelunasan sebesar Rp " . number_format($transaction->remaining_payment, 0, ',', '.') .
                " bisa dibayar saat pengambilan barang di counter.";
        } elseif ($status === 'paid') {
            $message .= "\n✅ Barang bisa diambil di counter sesuai jadwal.";
        }
        // Tampilkan link invoice jika transaksi baru dengan status pending atau paid
        if ($eventType === 'created' && in_array($status, ['pending', 'paid'])) {
            $message .= "\n\n📄 *Invoice dapat dilihat di sini:*\n" . url('/pdf/' . $transaction->id);
        }



        if ($eventType === 'updated') {
            $message .= "\n\n❗Jika kamu merasa tidak melakukan perubahan ini, segera hubungi admin ya.";
        }

        return $message;
    }

    protected function buildStatusChangeMessage(Transaction $transaction): string
    {
        $userName = $transaction->user->name;
        $transactionId = $transaction->booking_transaction_id;
        $newStatus = $transaction->booking_status;
        $oldStatus = $transaction->getOriginal('booking_status');

        $message = "Hai $userName! 👋\n";

        switch ($newStatus) {
            case 'canceled':
                $message .= "Transaksi sewa kamu (No: $transactionId) *dibatalkan*. Kalau kamu nggak merasa melakukan ini, segera hubungi admin ya.";
                break;
            case 'paid':
                $message .= "Terima kasih! Pembayaran untuk transaksi (No: $transactionId) sudah kami terima. Kamu bisa ambil barang di counter sesuai jadwal.";
                break;
            case 'rented':
                $message .= "Barang untuk transaksi (No: $transactionId) sudah diambil. Selamat menggunakan! 🎉 Kalau kamu nggak merasa mengambilnya, langsung hubungi admin ya.";
                break;
            case 'finished':
                $message .= "Transaksi sewa (No: $transactionId) telah selesai. Makasih udah sewa di Global Photo Rental! 🙌";
                break;
            default:
                $message .= "Status transaksi kamu (No: $transactionId) berubah dari *$oldStatus* jadi *$newStatus*.";
        }

        return $message;
    }
}
