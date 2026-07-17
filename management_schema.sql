-- Additional schema for Management functionality
-- Run this in addition to the existing schema.sql

CREATE TABLE management (
    managementid INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username     CHAR(50)  NOT NULL UNIQUE,
    password     CHAR(255) NOT NULL,
    fullname     CHAR(100) NOT NULL
);

-- Sample management user (password is 'admin123' hashed with PASSWORD_DEFAULT)
-- Replace the hash by registering through management_register.php in development,
-- or insert manually with password_hash() output.
-- Example:
-- INSERT INTO management (username, password, fullname)
-- VALUES ('admin', '$2y$10$REPLACE_WITH_REAL_HASH', 'System Administrator');
