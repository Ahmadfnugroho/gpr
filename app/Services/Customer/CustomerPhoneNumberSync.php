<?php

namespace App\Services\Customer;

use App\Models\Customer;

final class CustomerPhoneNumberSync
{
    /**
     * Sinkronisasi nomor HP customer (full replace, idempotent).
     */
    public function sync(Customer $customer, array $phones): void
    {
        // Normalisasi & deduplikasi
        $phones = collect($phones)
            ->filter()
            ->map(fn($phone) => $this->normalize($phone))
            ->unique()
            ->values();

        // Hapus semua nomor lama
        $customer->phoneNumbers()->delete();

        if ($phones->isEmpty()) {
            return;
        }

        // Insert ulang (deterministic)
        foreach ($phones as $index => $phone) {
            $customer->phoneNumbers()->create([
                'phone'      => $phone,
                'is_primary' => $index === 0,
            ]);
        }
    }

    private function normalize(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}
