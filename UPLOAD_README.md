# Peningkatan Sistem Upload File

## Perubahan yang Dilakukan

1. **Peningkatan Batas Ukuran Upload**
   - Batas ukuran file upload ditingkatkan dari 2MB menjadi 10MB
   - Perubahan dilakukan di `RegistrationController.php` pada validasi file

2. **Implementasi Kompresi Otomatis**
   - File dengan ukuran > 1MB akan dikompresi secara otomatis
   - Menggunakan library Intervention/Image untuk kompresi
   - Implementasi di kelas `ImageCompressor` di `app/Helpers/ImageCompressor.php`

3. **Konfigurasi Timeout**
   - Meningkatkan batas waktu eksekusi untuk upload file besar
   - Mengatur `set_time_limit(300)` (5 menit) di `RegistrationController`
   - Menambahkan file konfigurasi PHP di `public/upload.php.ini` dan `public/.user.ini`

## Cara Pengujian

1. **Pengujian Manual**
   - Upload file dengan ukuran 8-9MB melalui form registrasi
   - Verifikasi file berhasil diupload dan disimpan
   - Cek log untuk memastikan file > 1MB dikompresi

2. **Pengujian Otomatis**
   - Jalankan test dengan perintah: `php artisan test --filter=FileUploadTest`
   - Test mencakup:
     - Upload file besar (8MB)
     - Kompresi file > 1MB
     - Penolakan file > 10MB

## Catatan Penting

- Pastikan server memiliki konfigurasi PHP yang sesuai untuk mendukung upload file besar
- Jika menggunakan shared hosting, mungkin perlu menghubungi penyedia hosting untuk mengatur batas upload
- Untuk produksi, pertimbangkan untuk menggunakan penyimpanan eksternal seperti S3 untuk file besar