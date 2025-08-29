# Form Registrasi Global Photo Rental

## Overview
Form registrasi ini dibuat sesuai permintaan untuk memungkinkan calon penyewa mendaftar tanpa perlu login. Form ini mencakup semua field yang diperlukan dan mengirim notifikasi email ke admin setelah registrasi berhasil.

## Fitur yang Diimplementasi

### ✅ Section: Data Diri
1. **Email*** - Wajib, validasi email, dan verifikasi menggunakan Laravel Email Verification
2. **Mengetahui Global Photo Rental dari*** - Pilihan: Instagram, Teman, Google, Lainnya
3. **Nama Lengkap (Sesuai KTP)*** - Wajib
4. **Jenis Kelamin*** - Radio button: Laki-laki, Perempuan
5. **Alamat Tinggal Sekarang (Ditulis Lengkap)*** - Wajib, textarea
6. **No. HP 1*** - Wajib, auto format ke format Indonesia
7. **No. HP 2** - Opsional, auto format ke format Indonesia
8. **Pekerjaan** - Opsional
9. **Alamat Kantor** - Opsional, textarea
10. **Nama akun Instagram penyewa** - Opsional
11. **Foto KTP*** - Wajib, upload image (jpeg, png, jpg), max 2MB, disimpan ke `user_photos` table dengan `photo_type = 'ktp'`

### ✅ Section: Kontak Emergency
1. **Nama Kontak Emergency*** - Wajib
2. **No. HP Kontak Emergency*** - Wajib, auto format ke format Indonesia

## Alur Registrasi

1. **User mengisi form** di `/register`
2. **Validasi server-side** dengan pesan error dalam Bahasa Indonesia
3. **Simpan data user** dengan status default `blacklist`
4. **Upload foto KTP** ke storage dan simpan referensi di database
5. **Kirim email verifikasi** ke user dengan link verifikasi
6. **Kirim notifikasi email** ke admin (`imam.prabowo1511@gmail.com`) dengan link edit user
7. **Redirect ke halaman sukses** dengan instruksi selanjutnya

## URL dan Routes

- **Form Registrasi**: `GET /register` 
- **Proses Registrasi**: `POST /register`
- **Halaman Sukses**: `GET /registration/success`
- **Verifikasi Email**: `GET /email/verify/{id}/{hash}` (signed URL)

## Struktur Database

### Tabel `users`
- Menggunakan kolom yang sudah ada
- Status default: `blacklist`
- Email verification menggunakan kolom `email_verified_at`

### Tabel `user_phone_numbers`
- Menyimpan No. HP 1 dan No. HP 2 (jika ada)
- Relasi one-to-many dengan users

### Tabel `user_photos`
- Menyimpan foto KTP dengan `photo_type = 'ktp'`
- File disimpan di `storage/app/public/user_photos/`

## Email Notifications

### Email Verifikasi User
- Subject: "Verifikasi Email - Global Photo Rental"
- Berisi link verifikasi dengan expired time 60 menit
- Template dalam Bahasa Indonesia

### Email Notifikasi Admin
- Dikirim ke: `imam.prabowo1511@gmail.com`
- Subject: "Registrasi User Baru - Global Photo Rental"
- Berisi detail user dan link edit di admin panel

## Validasi dan Security

### Client-side
- Auto format nomor HP ke format Indonesia (+62)
- File type validation untuk foto KTP
- Required field indicators (*)

### Server-side
- Email validation dan uniqueness check
- Image file validation (jpeg, png, jpg, max 2MB)
- All required fields validation
- XSS protection dengan Laravel sanitization

## File Upload
- **Directory**: `storage/app/public/user_photos/`
- **Naming**: `ktp_{user_id}_{timestamp}.{extension}`
- **Max Size**: 2MB
- **Allowed Types**: jpeg, png, jpg
- **Storage**: Laravel Storage dengan disk 'public'

## Styling dan UI

### Framework
- **Bootstrap 5.1.3** untuk responsive design
- **Font Awesome 6.0** untuk icons
- **Custom CSS** dengan gradient background

### Features
- Responsive design untuk mobile dan desktop
- Loading states dan form feedback
- Error message display
- Success/failure notifications
- Auto-formatting untuk nomor HP

## Testing

### Manual Testing Checklist
- [ ] Form dapat diakses di `/register`
- [ ] Validasi client-side bekerja
- [ ] Validasi server-side dengan pesan error
- [ ] Upload foto KTP berhasil
- [ ] Data tersimpan di database dengan benar
- [ ] Email verifikasi terkirim ke user
- [ ] Email notifikasi terkirim ke admin
- [ ] Redirect ke halaman sukses
- [ ] Link verifikasi email bekerja

## Konfigurasi yang Diperlukan

### Environment Variables (.env)
```env
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=imam.prabowo1511@gmail.com
MAIL_PASSWORD=fnqrigrwwwqgiabi
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="imam.prabowo1511@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Storage Link
```bash
php artisan storage:link
```

## Troubleshooting

### Email tidak terkirim
- Periksa konfigurasi SMTP di `.env`
- Pastikan `MAIL_PASSWORD` menggunakan App Password untuk Gmail
- Cek log di `storage/logs/laravel.log`

### Upload foto gagal
- Pastikan folder `storage/app/public/user_photos/` ada dan writable
- Periksa `php.ini` untuk `upload_max_filesize` dan `post_max_size`
- Pastikan symbolic link storage sudah dibuat

### Database error
- Jalankan `php artisan migrate` untuk memastikan semua tabel up-to-date
- Periksa koneksi database di `.env`

## Customization

### Mengubah Email Admin
Edit di `RegistrationController::sendAdminNotification()`:
```php
$adminEmail = 'your-admin@email.com';
```

### Mengubah Status Default User
Edit di `RegistrationController::register()`:
```php
'status' => 'active', // atau status lain sesuai kebutuhan
```

### Menambah Field Baru
1. Tambahkan field di migration (jika diperlukan)
2. Update `$fillable` di model User
3. Tambahkan field di form view
4. Tambahkan validasi di controller

## Support

Untuk pertanyaan atau issues terkait form registrasi ini, hubungi developer atau check file log Laravel di `storage/logs/`.
