-- Enable pgcrypto extension for password hashing
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Add Admin User
-- Email: admin@test.com, Password: admin123
INSERT INTO users (first_name, last_name, email, password, job_title, access_level)
VALUES ('Admin', 'User', 'admin@test.com', crypt('admin123', gen_salt('bf')), 'Administrator', 'admin');

-- Add Normal User
-- Email: user@test.com, Password: user123
INSERT INTO users (first_name, last_name, email, password, job_title, access_level)
VALUES ('Test', 'User', 'user@test.com', crypt('user123', gen_salt('bf')), 'Employee', 'user');

-- Add another Admin User
-- Email: admin2@test.com, Password: password
INSERT INTO users (first_name, last_name, email, password, job_title, access_level)
VALUES ('Admin', 'Two', 'admin2@test.com', crypt('password', gen_salt('bf')), 'Manager', 'admin');

-- Verify users were created
SELECT id, first_name, last_name, email, access_level FROM users;
