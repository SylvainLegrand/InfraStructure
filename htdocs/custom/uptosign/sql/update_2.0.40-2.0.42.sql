
ALTER TABLE llx_uptosign CHANGE path_file path_file VARCHAR(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE llx_uptosign ADD path_file_signed VARCHAR(512) NULL AFTER path_file; 
