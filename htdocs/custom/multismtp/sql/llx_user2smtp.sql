CREATE TABLE IF NOT EXISTS llx_user2smtp
(
  fk_user INT PRIMARY KEY NOT NULL,
  smtp_server VARCHAR(255),
  smtp_port INT,
  smtp_tls INT,
  smtp_starttls INT,
  smtp_id VARCHAR(255),
  smtp_pw VARCHAR(255),
  imap_server VARCHAR(255),
  imap_port INT,
  imap_tls INT,
  imap_id VARCHAR(255),
  imap_pw VARCHAR(255),
  imap_folder VARCHAR(255),
  CONSTRAINT unique_fk_user UNIQUE (fk_user),
  CONSTRAINT fk_fk_user FOREIGN KEY (fk_user) REFERENCES llx_user (rowid)
);

/**
 * Upgrade from 1.0.3 to 1.1.1
 */
ALTER TABLE llx_user2smtp ADD imap_server VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD imap_port INT NULL;
ALTER TABLE llx_user2smtp ADD imap_tls INT NULL;
ALTER TABLE llx_user2smtp ADD imap_id VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD imap_pw VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD imap_folder VARCHAR(255) NULL;
-- For MySQL
ALTER TABLE llx_user2smtp MODIFY COLUMN smtp_id VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp MODIFY COLUMN smtp_pw VARCHAR(255) NULL;
-- For PostgreSQL
ALTER TABLE llx_user2smtp ALTER COLUMN smtp_id DROP NOT NULL;
ALTER TABLE llx_user2smtp ALTER COLUMN smtp_pw DROP NOT NULL;

/**
 * Upgrade from 1.2 to 1.3
 */
 ALTER TABLE llx_user2smtp ADD smtp_starttls INT NULL;
