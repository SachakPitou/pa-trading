-- AI Market Analyst: one review per signal (safe to re-run).
CREATE TABLE IF NOT EXISTS signal_ai(
  signal_id INT PRIMARY KEY,
  analysis MEDIUMTEXT NOT NULL,
  model VARCHAR(60) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(signal_id) REFERENCES signals(id) ON DELETE CASCADE) ENGINE=InnoDB;