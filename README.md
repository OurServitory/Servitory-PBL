# Servitory-PBL

## Mata Kuliah Kolaborator
- Desain dan Pemrograman Web
- Basis Data Lanjut
- Sistem Informasi Manajemen
- Manajemen Proyek

## Member:
- Agrisyandi Maraja Hutabarat - Shan0n73 - 254107020071
- Muhammad Anandito Palawarukka - MeAruka - 254107020220
- Muhammad Wasi' Nurul Ilmi - wasikwwasikm - 254107020135
- Rakha Madani Rizkullah - rakhamadani - 254107020073

## Menyiapkan database

Proyek menggunakan PostgreSQL. Jalankan `Halaman/sql/01_create_database.sql`
sebagai pengguna yang memiliki izin membuat database, lalu pilih database
`servitory` dan jalankan `Halaman/sql/02_schema.sql` untuk membuat tabel
pengguna dan laporan.

Untuk database yang sudah dibuat sebelum dukungan status penolakan laporan,
jalankan `Halaman/sql/04_tambah_status_ditolak.sql`. Data akun teknisi demo
dibuat lewat `Halaman/sql/03_seed_teknisi_dummy.sql`. Akun `teknisi01` sampai
`teknisi10` memakai password demo `Teknisi123!`; ganti password sebelum
menggunakan aplikasi di luar lingkungan lokal.

Untuk database yang sudah ada sebelum penghapusan jenis kendala, jalankan
`Halaman/sql/05_hapus_jenis_kendala.sql` setelah migrasi lainnya.

Alur Koordinator tersedia melalui `Halaman/sql/06_tambah_alur_koordinator.sql`
untuk database yang sudah ada. Laporan baru menunggu Koordinator menetapkan
urgensi (Tinggi, Menengah, atau Rendah) dan mengonfirmasi sebelum tampil bagi
Teknisi. Untuk instalasi baru, skema pada `02_schema.sql` sudah menyertakan
peran dan alur tersebut. Akun Koordinator lokal demo dapat dibuat melalui
`Halaman/sql/07_seed_koordinator_demo.sql` dengan password `Teknisi123!`;
ganti password sebelum penggunaan di luar lingkungan lokal.

Jika database lama masih memiliki kolom `issue` wajib yang tidak lagi dipakai
form laporan, jalankan `Halaman/sql/08_perbaiki_kolom_issue_lama.sql` agar
kolom tersebut boleh kosong tanpa menghapus nilai laporan yang tersimpan.

Konfigurasi koneksi PHP ada di `Halaman/includes/koneksi.php`. Sesuaikan host, port,
username, dan password dengan konfigurasi PostgreSQL lokal Anda.

Halaman Pelapor, Teknisi, dan Koordinator memperbarui data otomatis setiap 5 detik
selama tab sedang aktif. Perubahan dari pengguna atau tab lain akan terlihat tanpa
memuat ulang halaman; pembaruan ditunda saat formulir sedang digunakan. Lokasi pada
form laporan menyarankan ruang yang pernah digunakan, tetapi tetap dapat diketik
atau diubah secara manual. Setiap tab login memiliki sesi akun sendiri, jadi
beberapa pengguna dapat masuk pada tab berbeda di browser yang sama; tombol Keluar
hanya mengakhiri sesi pada tab tersebut.
