<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'maison1815');
define('DB_USER', 'root');
define('DB_PASS', 'SQLadmin');
define('BASE_PATH', __DIR__);
define('BASE_URL', 'http://maison1815.ch.test');
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('MAX_VIDEO_SIZE', 2 * 1024 * 1024 * 1024); // 2GB
define('ALLOWED_VIDEO_FORMATS', ['mp4', 'mov', 'avi', 'mkv', 'webm', 'wmv', 'm4v', 'flv']);
define('ALLOWED_IMAGE_FORMATS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
