-- Create administrator user for production
-- Username: administrator@root
-- Password: F@iz@n!984

-- Check if user exists first
SELECT id, username, email FROM users WHERE username = 'administrator@root';

-- If user doesn't exist, run this INSERT:
INSERT INTO users (username, name, email, password, created_at, updated_at)
VALUES (
    'administrator@root',
    'Administrator',
    'admin@hajj.sibu.org.my',
    '$2y$12$H1uU3vUNx7F7VPVtxGV2yecrUQV/zClu0lrz7hdwS49d6jRjzr2B6',
    NOW(),
    NOW()
);

-- Verify user was created
SELECT id, username, name, email, created_at FROM users WHERE username = 'administrator@root';
