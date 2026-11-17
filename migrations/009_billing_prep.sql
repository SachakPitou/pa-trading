CREATE TABLE IF NOT EXISTS subscriptions(
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  provider ENUM('card','aba') NOT NULL,
  provider_ref VARCHAR(120) NULL,          -- processor's customer/token id, never raw card numbers
  status ENUM('pending','active','past_due','canceled') NOT NULL DEFAULT 'pending',
  current_period_end DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id,status),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;