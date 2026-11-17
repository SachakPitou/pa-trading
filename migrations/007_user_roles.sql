-- Roles: every existing account stays 'user', except the oldest account (your owner account) becomes 'admin'.
ALTER TABLE users ADD COLUMN role ENUM('admin','user') NOT NULL DEFAULT 'user' AFTER pass_hash;
UPDATE users SET role='admin' WHERE id=(SELECT id FROM (SELECT MIN(id) AS id FROM users) t);