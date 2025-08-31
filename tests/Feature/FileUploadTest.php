<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_upload_large_files_up_to_10mb()
    {
        // Buat file palsu dengan ukuran 8MB (di bawah batas 10MB)
        $largeFile = UploadedFile::fake()->image('ktp.jpg')->size(8000);

        // Data registrasi
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'gender' => 'male',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'village' => 'Gandaria Utara',
            'address_detail' => 'Jl. Test No. 123',
            'phone1' => '08123456789',
            'source_info' => 'Instagram',
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_number' => '08987654321',
            'ktp_photo' => $largeFile,
            'id_photo' => UploadedFile::fake()->image('id1.jpg'),
            'id_type' => 'SIM',
            'id_photo_2' => UploadedFile::fake()->image('id2.jpg'),
            'id_type_2' => 'NPWP',
        ];

        // Kirim request ke endpoint registrasi
        $response = $this->post('/register', $data);

        // Verifikasi redirect ke halaman sukses
        $response->assertRedirect(route('registration.success'));

        // Verifikasi file tersimpan
        Storage::disk('public')->assertExists('user_photos/' . $largeFile->hashName());

        // Verifikasi user dibuat di database
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    /** @test */
    public function it_compresses_files_larger_than_1mb()
    {
        // Buat file palsu dengan ukuran 2MB (di atas batas kompresi 1MB)
        $largeFile = UploadedFile::fake()->image('ktp.jpg')->size(2000);

        // Data registrasi
        $data = [
            'email' => 'compress@example.com',
            'name' => 'Compress Test',
            'gender' => 'female',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'village' => 'Gandaria Utara',
            'address_detail' => 'Jl. Test No. 123',
            'phone1' => '08123456789',
            'source_info' => 'Instagram',
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_number' => '08987654321',
            'ktp_photo' => $largeFile,
            'id_photo' => UploadedFile::fake()->image('id1.jpg'),
            'id_type' => 'SIM',
            'id_photo_2' => UploadedFile::fake()->image('id2.jpg'),
            'id_type_2' => 'NPWP',
        ];

        // Kirim request ke endpoint registrasi
        $response = $this->post('/register', $data);

        // Verifikasi redirect ke halaman sukses
        $response->assertRedirect(route('registration.success'));

        // Verifikasi user dibuat di database
        $this->assertDatabaseHas('users', [
            'email' => 'compress@example.com',
            'name' => 'Compress Test',
        ]);
    }

    /** @test */
    public function it_rejects_files_larger_than_10mb()
    {
        // Buat file palsu dengan ukuran 11MB (di atas batas 10MB)
        $tooLargeFile = UploadedFile::fake()->image('ktp_large.jpg')->size(11000);

        // Data registrasi
        $data = [
            'email' => 'toolarge@example.com',
            'name' => 'Too Large Test',
            'gender' => 'male',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'village' => 'Gandaria Utara',
            'address_detail' => 'Jl. Test No. 123',
            'phone1' => '08123456789',
            'source_info' => 'Instagram',
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_number' => '08987654321',
            'ktp_photo' => $tooLargeFile,
            'id_photo' => UploadedFile::fake()->image('id1.jpg'),
            'id_type' => 'SIM',
            'id_photo_2' => UploadedFile::fake()->image('id2.jpg'),
            'id_type_2' => 'NPWP',
        ];

        // Kirim request ke endpoint registrasi
        $response = $this->post('/register', $data);

        // Verifikasi error validasi
        $response->assertSessionHasErrors('ktp_photo');

        // Verifikasi user tidak dibuat di database
        $this->assertDatabaseMissing('users', [
            'email' => 'toolarge@example.com',
        ]);
    }
}
