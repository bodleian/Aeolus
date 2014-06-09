<?php
/*
 * Author: Sushila Burgess
 *
 */

define( 'CONSTANT_LIVE_DATABASE_NAME', 'nameoflivedb' );
define( 'CONSTANT_TEST_DATABASE_NAME', 'nameoftestdb' );
define( 'CONSTANT_DEV_DATABASE_NAME',  'nameoftestdb' );

define( 'CONSTANT_MINIMAL_USER', 'x0minim' );
define( 'CMS_DB_USERNAME', 'w5view' );  # user with read-only role when doing publicly available search via CMS
define( 'HELP_DB_USERNAME', 'w5view' ); # user with read-only permissions on help pages (without login)

define( 'SUPERVISOR_ROLE_CODE', 'a1supr' );
define( 'SUPERVISOR_ROLE_ID',   99       );
define( 'SUPERVISOR_INITIAL_PASSWORD', 'ChangeMeNowPlease!' ); # The password in an *EMPTY* database

define( 'EDITOR_ROLE_CODE', 'b2edit' );
define( 'VIEWER_ROLE_CODE', 'c3view' );

define( 'DB_LOGFILE_DIR', '/path/ending/in/slash/' );

?>
