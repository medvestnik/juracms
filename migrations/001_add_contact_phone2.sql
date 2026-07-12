-- Add second phone number setting
INSERT IGNORE INTO `jura_settings` (setting_key, setting_value, setting_type, group_name)
VALUES ('contact_phone2', '', 'string', 'contacts');
