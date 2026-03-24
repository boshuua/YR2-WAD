-- 12_add_reset_flag_to_users.sql
-- Add a flag to indicate if a user must reset their password upon login

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'requires_password_reset') THEN
        ALTER TABLE users ADD COLUMN requires_password_reset BOOLEAN DEFAULT FALSE;
    END IF;
END $$;
