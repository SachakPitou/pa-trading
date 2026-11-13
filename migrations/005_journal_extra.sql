CREATE TABLE IF NOT EXISTS journal_extra(
  journal_id INT PRIMARY KEY,
  sl DECIMAL(12,3) NULL, tp DECIMAL(12,3) NULL,
  planned_r DECIMAL(6,2) NULL, actual_r DECIMAL(6,2) NULL,
  entry_reason TEXT NULL, exit_reason TEXT NULL,
  emotion VARCHAR(20) NULL, discipline TINYINT NULL,
  notes TEXT NULL, rules TEXT NULL,
  shot_entry VARCHAR(60) NULL, shot_exit VARCHAR(60) NULL,
  FOREIGN KEY(journal_id) REFERENCES journal(id) ON DELETE CASCADE) ENGINE=InnoDB;