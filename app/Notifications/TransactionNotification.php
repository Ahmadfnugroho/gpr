<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Services\WAHAService;
use App\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class TransactionNotification extends Notification
{
    use Queueable, SerializesModels;

    protected Transaction $transaction;
    protected string $eventType;

    public function __construct(Transaction $transaction, string $eventType)
    {
        $this->transaction = $transaction;
        $this->eventType = $eventType;
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        // Email notification
        if (!config('notifications.disable_transaction_email')) {
            $channels[] = 'mail';
        }

        // WhatsApp notification - selalu aktif
        $channels[] = WhatsAppChannel::class;

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = $this->transaction;
        $status = $t->booking_status;
        $transactionId = $t->booking_transaction_id;

        [$subject, $intro] = $this->buildSubjectAndIntro($transactionId, $status, $this->eventType, $notifiable->name);

        $invoiceUrl = url("/pdf/{$t->id}");

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hai {$notifiable->name},")
            ->line($intro)
            ->line("🗓️ Tanggal Sewa: {$t->start_date->format('d M Y')} s.d. {$t->end_date->format('d M Y')}")
            ->line("💰 Total: Rp " . number_format($t->grand_total, 0, ',', '.'));

        if ($t->promo?->name) {
            $mail->line("🎁 Promo: {$t->promo->name}");
        }

        if ($t->note) {
            $mail->line("📝 Catatan: {$t->note}");
        }

        // Tambahkan link invoice hanya untuk kondisi tertentu
        if (
            ($this->eventType === 'created' && in_array($status, ['booking', 'paid'])) ||
            ($this->eventType === 'updated' && $t->getOriginal('booking_status') === 'booking' && $status === 'paid')
        ) {
            $mail->line('🧾 Klik tombol di bawah untuk melihat invoice transaksi kamu:')
                ->action('Lihat Invoice', $invoiceUrl);
        }

        $mail->line('Terima kasih telah menggunakan layanan kami.');

        return $mail;
    }

    /**
     * Send WhatsApp notification
     */
    public function toWhatsapp($notifiable)
    {
        try {
            $wahaService = new WAHAService();
            $result = $wahaService->sendTransactionNotification($this->transaction, $this->eventType);

            if ($result) {
                // Log::info('WhatsApp transaction notification sent', [
                //     'user_id' => $notifiable->id,
                //     'transaction_id' => $this->transaction->id,
                //     'event_type' => $this->eventType
                // ]);
            }

            return $result;
        } catch (\Exception $e) {
            // Log::error('Failed to send WhatsApp transaction notification', [
            //     'user_id' => $notifiable->id,
            //     'transaction_id' => $this->transaction->id,
            //     'error' => $e->getMessage()
            // ]);
            return false;
        }
    }

    protected function buildSubjectAndIntro(string $transactionId, string $status, string $eventType, string $userName): array
    {
        $subject = '';
        $intro = '';

        if ($eventType === 'created') {
            if ($status === 'booking') {
                $subject = "Transaksi {$transactionId} berhasil dibuat";
                $intro = "Transaksi kamu dengan ID {$transactionId} berhasil dibuat dan sedang menunggu pembayaran.";
            } elseif ($status === 'paid') {
                $subject = "Transaksi {$transactionId} berhasil dan telah dibayar";
                $intro = "Transaksi kamu dengan ID {$transactionId} berhasil dibuat dan pembayaran sudah kami terima.";
            }
        } elseif ($eventType === 'updated') {
            switch ($status) {
                case 'paid':
                    $subject = "Transaksi {$transactionId} telah lunas";
                    $intro = "Pembayaran untuk transaksi kamu dengan ID {$transactionId} telah kami terima.";
                    break;
                case 'on_rented':
                    $subject = "Transaksi {$transactionId} telah diambil";
                    $intro = "Barang untuk transaksi kamu dengan ID {$transactionId} telah diambil. Selamat menggunakan!";
                    break;
                case 'done':
                    $subject = "Transaksi {$transactionId} selesai";
                    $intro = "Transaksi sewa kamu dengan ID {$transactionId} telah selesai. Terima kasih telah menggunakan layanan kami.";
                    break;
                case 'cancel':
                    $subject = "Transaksi {$transactionId} dibatalkan";
                    $intro = "Transaksi kamu dengan ID {$transactionId} telah dibatalkan. Jika kamu merasa tidak melakukan ini, segera hubungi admin.";
                    break;
                default:
                    $subject = "Transaksi {$transactionId} diperbarui";
                    $intro = "Ada pembaruan pada transaksi kamu dengan ID {$transactionId}. Status sekarang: *{$status}*.";
            }
        }

        return [$subject, $intro];
    }
}
