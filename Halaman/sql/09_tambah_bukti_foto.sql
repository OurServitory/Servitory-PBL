BEGIN;

ALTER TABLE reports
    ADD COLUMN IF NOT EXISTS evidence_data BYTEA,
    ADD COLUMN IF NOT EXISTS evidence_mime VARCHAR(32);

ALTER TABLE reports
    DROP CONSTRAINT IF EXISTS reports_evidence_check;

ALTER TABLE reports
    ADD CONSTRAINT reports_evidence_check
    CHECK (
        (evidence_data IS NULL AND evidence_mime IS NULL)
        OR (
            evidence_data IS NOT NULL
            AND evidence_mime IS NOT NULL
            AND evidence_mime IN ('image/jpeg', 'image/png', 'image/webp')
        )
    );

COMMIT;
