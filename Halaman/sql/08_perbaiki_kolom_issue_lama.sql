BEGIN;

DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'reports'
          AND column_name = 'issue'
    ) THEN
        ALTER TABLE reports
            ALTER COLUMN issue DROP NOT NULL;
    END IF;
END
$$;

COMMIT;
