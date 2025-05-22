<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class GoogleSheetSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_endpoint_with_valid_data()
    {
        $headers = ['Content-Type' => 'application/json'];

        $payload = [
            'values' => [
                [
                    'Email Address',
                    'Nama Lengkap (Sesuai KTP)',
                    'Alamat Tinggal Sekarang (Ditulis Lengkap)',
                    'Pekerjaan',
                    'Alamat Kantor',
                    'Nama akun Instagram penyewa',
                    'Nama Kontak Emergency',
                    'No. Hp Kontak Emergency',
                    'Jenis Kelamin',
                    'Mengetahui Global Photo Rental dari',
                    'STATUS',
                    'No. Hp1',
                    'No. Hp2',
                    'updated_at',
                ],
                [
                    'test@example.com',
                    'Test User',
                    'Test Address',
                    'Tester',
                    'Test Office',
                    'test_insta',
                    'Emergency Contact',
                    '08123456789',
                    'Laki-laki',
                    'Internet',
                    'Aktif',
                    '08123456789',
                    '08123456780',
                    Carbon::now()->toIso8601String(),
                ],
            ],
        ];

        $response = $this->postJson('/api/google-sheet-sync', $payload, $headers);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'name' => 'Test User']);
        $this->assertDatabaseHas('user_phone_numbers', ['phone_number' => '08123456789']);
    }

    public function test_sync_endpoint_with_invalid_data()
    {
        $payload = ['values' => []]; // no data

        $response = $this->postJson('/api/google-sheet-sync', $payload);

        $response->assertStatus(400);
    }

    public function test_export_endpoint_without_since()
    {
        User::factory()->create([
            'email' => 'user1@example.com',
            'updated_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->getJson('/api/google-sheet-export');

        $response->assertStatus(200);
        $response->assertJsonStructure(['values']);
        $this->assertContains('user1@example.com', json_encode($response->json('values')));
    }

    public function test_export_endpoint_with_since()
    {
        $oldUser = User::factory()->create([
            'email' => 'olduser@example.com',
            'updated_at' => Carbon::now()->subDays(5),
        ]);

        $newUser = User::factory()->create([
            'email' => 'newuser@example.com',
            'updated_at' => Carbon::now()->subDay(),
        ]);

        $since = Carbon::now()->subDays(3)->toIso8601String();

        $response = $this->getJson('/api/google-sheet-export?since=' . urlencode($since));

        $response->assertStatus(200);
        $response->assertJsonStructure(['values']);
        $jsonValues = $response->json('values');

        if (is_array($jsonValues)) {
            $emails = array_column(array_slice($jsonValues, 1), 0); // skip headers
        } else {
            $emails = [];
        }

        $this->assertNotContains('olduser@example.com', $emails);
        $this->assertContains('newuser@example.com', $emails);
    }
}
