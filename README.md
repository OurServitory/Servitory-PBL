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

Laporan dapat menyertakan foto bukti opsional (JPG, PNG, atau WebP, maksimal
5 MB). Untuk database yang sudah ada, jalankan `Halaman/sql/09_tambah_bukti_foto.sql`.
Untuk instalasi baru, kolom bukti foto sudah tercakup di `02_schema.sql`.
Foto disimpan sebagai data biner di PostgreSQL dan hanya dapat dibuka oleh
pengguna yang berhak melihat laporannya.

Semua peran dapat membuka halaman Profil untuk melihat informasi akun dan
mengunggah foto profil (JPG, PNG, atau WebP, maksimal 5 MB). Untuk database
yang sudah ada, jalankan `Halaman/sql/12_tambah_foto_profil.sql`. Instalasi
baru sudah menyertakan kolom foto profil di `02_schema.sql`.

Waktu penyelesaian laporan dicatat saat Teknisi mengubah status menjadi
`Selesai`. Untuk database yang sudah ada, jalankan
`Halaman/sql/10_tambah_tanggal_selesai.sql`. Laporan lama yang sudah berstatus
`Selesai` tidak memiliki waktu penyelesaian historis sehingga kolomnya tetap
kosong.

Saat menandai laporan selesai, Teknisi dapat mengunggah foto bukti perbaikan
opsional (JPG, PNG, atau WebP, maksimal 5 MB). Untuk database yang sudah ada,
jalankan `Halaman/sql/11_tambah_bukti_foto_perbaikan.sql`. Foto perbaikan
ditampilkan terpisah dari foto awal laporan di dasbor Pelapor.

Konfigurasi koneksi PHP ada di `Halaman/includes/koneksi.php`. Sesuaikan host, port,
username, dan password dengan konfigurasi PostgreSQL lokal Anda.

Halaman Pelapor, Teknisi, dan Koordinator memperbarui data otomatis setiap 5 detik
selama tab sedang aktif. Perubahan dari pengguna atau tab lain akan terlihat tanpa
memuat ulang halaman; pembaruan ditunda saat formulir sedang digunakan. Lokasi pada
form laporan menyarankan ruang yang pernah digunakan, tetapi tetap dapat diketik
atau diubah secara manual. Setiap tab login memiliki sesi akun sendiri, jadi
beberapa pengguna dapat masuk pada tab berbeda di browser yang sama; tombol Keluar
hanya mengakhiri sesi pada tab tersebut.

## Retensi laporan otomatis

Laporan dan foto buktinya yang berumur lebih dari 30 hari berdasarkan
`reported_at` akan dihapus setiap hari pukul 03.00 melalui Windows Task
Scheduler. Akun pengguna tidak dihapus. Jalankan pemeriksaan tanpa menghapus
data terlebih dahulu:

```powershell
php .\maintenance\cleanup_old_reports.php --dry-run
```

Setelah memeriksa jumlah laporan yang akan dihapus, daftarkan tugas terjadwal
untuk akun Windows saat ini:

```powershell
powershell -ExecutionPolicy Bypass -File .\maintenance\register_report_cleanup_task.ps1
```

Tugas berjalan saat akun Windows sedang masuk. Jika jadwal terlewat ketika
perangkat tidak tersedia, tugas dijalankan saat tersedia kembali.
