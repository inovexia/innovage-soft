<?php
/** Enable W3 Total Cache */
define('WP_CACHE', true); // Added by W3 Total Cache


/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'easycoach_wp389' );

/** Database username */
define( 'DB_USER', 'easycoach_wp389' );

/** Database password */
define( 'DB_PASSWORD', '02S]1!pf9r' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'vyjlvd5ohewknaponnwde26jqjsiate5d7bckgmkgxajznywaby5wuwi5xigjyxr' );
define( 'SECURE_AUTH_KEY',  'tnm1bxulliwvg51pahhekcnszecdz4jywehl2rbmt8exxbnm1lwkv8hc55rzwud5' );
define( 'LOGGED_IN_KEY',    'xmv0otzq824xoslcaadl5zgpcqsmrbzwrbtzdjfxvlpxmy4qtu7ya2rnngkeigq7' );
define( 'NONCE_KEY',        'mj0mjh6vnlonmvlxgxsmx0l5av1lzexl3ma3snzefddh2exrxmjdwd3j9pchw1pf' );
define( 'AUTH_SALT',        'ilcwiqwhq3atpceomm84dw0mgtifzakte6j7ha6teppohzoodw27odpedora8cwv' );
define( 'SECURE_AUTH_SALT', 'qkxdivta8xqgaieww7ccxc2hgxjisaigef3ti05f81szwfiwla4r1hl9uy1ctokn' );
define( 'LOGGED_IN_SALT',   'usat4dejywte8ydgectecoradzs4ljne3g7lklhg2w7ruz2g2wd0wndbysoiantl' );
define( 'NONCE_SALT',       'ajvkomm5863egaiew21ufwgflxd41jtf2pvfvvf1ajvsdveygwxvo7bnmmxc0gw2' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wphj_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
