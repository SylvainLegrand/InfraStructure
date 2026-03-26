-- Multismtp - Upgrade to 1.4.13
-- Add OAuth2 fields for SMTP and IMAP

ALTER TABLE llx_user2smtp ADD COLUMN smtp_auth_type VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN smtp_oauth_service VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN smtp_oauth_provider VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN smtp_oauth_id VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN smtp_oauth_secret VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN smtp_oauth_url_authorize VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN smtp_oauth_scope TEXT NULL;
ALTER TABLE llx_user2smtp ADD COLUMN smtp_oauth_tenant VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_auth_type VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_oauth_service VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_oauth_provider VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_oauth_id VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_oauth_secret VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_oauth_url_authorize VARCHAR(255) NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_oauth_scope TEXT NULL;
ALTER TABLE llx_user2smtp ADD COLUMN imap_oauth_tenant VARCHAR(255) NULL;
