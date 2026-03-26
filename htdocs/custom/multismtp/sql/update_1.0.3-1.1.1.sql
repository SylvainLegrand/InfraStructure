-- Multismtp - Upgrade from 1.0.3 to 1.1.1
-- Add IMAP fields and make smtp_id/smtp_pw nullable

ALTER TABLE llx_user2smtp ADD COLUMN imap_server VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_port INT NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_tls INT NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_id VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_pw VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_folder VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp MODIFY COLUMN smtp_id VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp MODIFY COLUMN smtp_pw VARCHAR(255) NULL;
