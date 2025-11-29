<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'wordpress' );

/** Database password */
define( 'DB_PASSWORD', 'wordpress' );

/** Database hostname */
define( 'DB_HOST', 'mysql' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          'P29e{t.!:p8Bhasg8&*NgwkgI(=WY>&:U<-/E~MsCEu)SFUiO.E </d3{nu&1Vip' );
define( 'SECURE_AUTH_KEY',   '-h:o<`0^Is8&(RY} .~nvnKriD&BP(IYwdUI3Dp:o8~aH<8%wj<vdkS.T6QI%!`k' );
define( 'LOGGED_IN_KEY',     'rk[5~O9wqSO.dV=mjZJreYL8s2w!HyU& 8,-+3U?FsMPEwF>3e}t,P7!mTY@(~  ' );
define( 'NONCE_KEY',         '];;@t &Rw&l)w]LmDn_46sRdwpmUpO)-Q=b;vF_]il*m?Q2&ak]YUj>N^a~o#J~*' );
define( 'AUTH_SALT',         '.IJL_& n.+*[@ylF.KKp,6BQh6;4*yPOiyXp9Zf/,xG!]9CpL-~OsX}tTqk/10Y4' );
define( 'SECURE_AUTH_SALT',  ';OD]._a^Xb`(X01<49GPOfvMwcSg. +-ixWTRk7/*}Z3IT>87M{2q`k}}O@%<$fL' );
define( 'LOGGED_IN_SALT',    'P~_p:h-[N%k<rC!-W3j)8 Moj(24RNNt&e]CK*&?98VsWZEy?/MAaW:,{ChE-17,' );
define( 'NONCE_SALT',        'Ur6~J.sp:1G#k.:r&(x75KKNw`H:=QP8W_Of:XsZx$9d92zQ:k)bWzY*MjHgueor' );
define( 'WP_CACHE_KEY_SALT', 'p+E7`qpyqh/o~+)^M1C],7m+wyF4T_SA=^3T0 LZejEeQDC~;Un1`Shm`>.C8k7<' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
