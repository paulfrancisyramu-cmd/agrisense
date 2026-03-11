-- Add full_name column to users table for display and verification
ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(255);

-- ensure existing admin has a sensible name
UPDATE users SET full_name = 'Administrator' WHERE username = 'admin';
