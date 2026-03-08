-- Add role column to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'farmer';

-- Update admin user to have admin role
UPDATE users SET role = 'admin' WHERE username = 'admin';

-- Create farmer1 user with farmer role
INSERT INTO users (username, password, role) VALUES ('farmer1', 'farmer', 'farmer')
ON CONFLICT (username) DO NOTHING;
