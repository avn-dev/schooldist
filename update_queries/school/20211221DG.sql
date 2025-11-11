UPDATE kolumbus_forms_pages_blocks_settings SET value = CONCAT('["', value, '"]') WHERE setting = 'provider' AND INSTR(value, '"') = 0
