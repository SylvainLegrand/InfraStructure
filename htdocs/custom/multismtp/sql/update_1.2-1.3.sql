-- Multismtp - Upgrade from 1.2 to 1.3
-- Add SMTP STARTTLS field

ALTER TABLE llx_user2smtp ADD COLUMN smtp_starttls INT NULL;
