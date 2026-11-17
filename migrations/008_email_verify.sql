ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER role;
UPDATE users SET email_verified_at = NOW();
CREATE TABLE IF NOT EXISTS email_tokens(
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;