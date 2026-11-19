ALTER TABLE reports
    DROP CONSTRAINT IF EXISTS reports_status_check;

ALTER TABLE reports
    ADD CONSTRAINT reports_status_check
    CHECK (status IN ('Menunggu Konfirmasi', 'Diterima', 'Dijadwalkan', 'Selesai', 'Ditolak'));
