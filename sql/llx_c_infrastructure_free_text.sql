CREATE TABLE IF NOT EXISTS  llx_c_infrastructure_free_text (
    rowid    integer AUTO_INCREMENT PRIMARY KEY,
    label    varchar(255) NOT NULL,
    content  text,
    active   tinyint DEFAULT 1 NOT NULL,
    entity   integer  DEFAULT 1 NOT NULL
)ENGINE=innodb;