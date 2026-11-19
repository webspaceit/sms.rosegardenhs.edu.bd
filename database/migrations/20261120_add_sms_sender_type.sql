-- Run once on an existing installation to enable masking/non-masking routing.
ALTER TABLE sms_settings
    ADD COLUMN sender_type VARCHAR(20) NOT NULL DEFAULT 'masking' AFTER api_secret;

-- Set sender_type to 'non_masking' for gateways configured with a numeric CLI.
-- Keep 'masking' for an approved alphanumeric brand Sender ID.
