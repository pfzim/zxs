<?php
	define("DB_CPAGE", "utf8");
	define("DB_PREFIX", "zxs_");
	define("DB_NAME", "zxs");
	define("DB_HOST", "localhost");
	define("DB_USER", "zxs");
	define("DB_PASSWD", "your-password-here");

	define("MAIL_HOST", "smtp.example.com");
	define("MAIL_FROM", "admin@example.com");
	define("MAIL_FROM_NAME", "Robot");
	define("MAIL_ADMIN", "admin@example.com");
	define("MAIL_ADMIN_NAME", "Admin");
	define("MAIL_AUTH", true);
	define("MAIL_LOGIN", "robot@example.com");
	define("MAIL_PASSWD", "smtp-password-here");
	define("MAIL_SECURE", "tls");
	define("MAIL_PORT", 587);

	define("UPLOAD_DIR", "/var/www/box.example.com/upload");
	define("ALLOW_MAILS", '^.+@.+$');

	define("LOG_LOGIN", 1);
	define("LOG_DOWNLOAD", 2);			// p1 - file_id
	define("LOG_LOGIN_FAILED", 3);
	define("LOG_VIEW_ABOUT", 4);
	define("LOG_TAR_CREATE", 5);		// p1 - link_id
	define("LOG_LOGIN_ACTIVATE", 6);
	define("LOG_VIEW_STATS", 7);
