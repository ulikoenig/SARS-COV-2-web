<?php
define("DEBUG", true);
define("TEMPDIR",sys_get_temp_dir().DIRECTORY_SEPARATOR);

class Config {
    	const SQLSERVER = "mysql.server.net";
	const DATABASE = "corona-db-name";
	const TABLE = "RKI_TABLE_NAME";
	const USER = "benutzername";
	const PW = "sehrSicheresPasswort";
}
