<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'doafunddemo');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '7>nY<e+f7ViB3d#%59g1z=;Rk]]IF?^LAz/H%2S:AA;>]|<A{.=o|mAd7`l9H|8j');
define('SECURE_AUTH_KEY',  'r]YcOs8OQPtKq+S3 ^v+nW,G]we{AWZSH;O?!Qv-3ArhQm&7O?JLY703}7+q^u>P');
define('LOGGED_IN_KEY',    'yE#+Fww(:HvN}Ci$f&&65k}  zCBQ#K4Lo>7y_vk+o[`J-nzG^TiBWE5@R^EX5+w');
define('NONCE_KEY',        'poUoNNT;f4/M:qF-|tZdmC6/J,^T+DO:#+zK@B{cwUX/Y?6![G1QD8@rR=~xO]bX');
define('AUTH_SALT',        '|TOqo#HiC7*wAptUHf^cahAcD1<fZD8$e+l1G~~n!iCAlD5H>1]$,~eLT8M@]13O');
define('SECURE_AUTH_SALT', 'B4vBQmsfTSV,x;2.B`h?AZMj^-8&9H1E(X*j|al0<Cj0M W]+=S l<c[&iud U*5');
define('LOGGED_IN_SALT',   'JG?+rVh5|@E>r>!6uf%lqx799G@dm2WR;7-pC5!LdNU48$/u6y-/<#5eoeTWSa}/');
define('NONCE_SALT',       'NZY<$#!v)zq#!uG^M Od<TV^21[xan>AD9v|-P3 ,N*ML@~5(>+p|(|Qr.^O# 8n');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

define('FS_METHOD', 'direct');
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
