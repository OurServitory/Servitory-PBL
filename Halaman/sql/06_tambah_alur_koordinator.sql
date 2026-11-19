BEGIN;

ALTER TABLE users
    DROP CONSTRAINT IF EXISTS users_role_check;

ALTER TABLE users
    ADD CONSTRAINT users_role_check
    CHECK (role IN ('pelapor', 'teknisi', 'koordinator'));

ALTER TABLE reports
    ADD COLUMN IF NOT EXISTS urgency VARCHAR(10),
    ADD COLUMN IF NOT EXISTS confirmed_at TIMESTAMPTZ;

-- Existing reports were already available to technicians before coordinator review.
UPDATE reports
SET urgency = 'Menengah',
    confirmed_at = COALESCE(confirmed_at, reported_at)
WHERE status <> 'Menunggu Konfirmasi'
  AND (urgency IS NULL OR confirmed_at IS NULL);

ALTER TABLE reports
    DROP CONSTRAINT IF EXISTS reports_status_check;

ALTER TABLE reports
    ALTER COLUMN status TYPE VARCHAR(30),
    ALTER COLUMN status SET DEFAULT 'Menunggu Konfirmasi';

ALTER TABLE reports
    ADD CONSTRAINT reports_status_check
    CHECK (status IN ('Menunggu Konfirmasi', 'Diterima', 'Dijadwalkan', 'Selesai', 'Ditolak'));

ALTER TABLE reports
    DROP CONSTRAINT IF EXISTS reports_urgency_check;

ALTER TABLE reports
    ADD CONSTRAINT reports_urgency_check
    CHECK (urgency IS NULL OR urgency IN ('Rendah', 'Menengah', 'Tinggi'));

ALTER TABLE reports
    DROP CONSTRAINT IF EXISTS reports_urgency_confirmation_check;

ALTER TABLE reports
    ADD CONSTRAINT reports_urgency_confirmation_check
    CHECK ((urgency IS NULL) = (confirmed_at IS NULL));

COMMIT;
