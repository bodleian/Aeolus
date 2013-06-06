<?php
/*
 * Sets database name for use in DBQuery
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */

require_once 'private_defines.php';
require_once 'public_defines.php';

if( CONSTANT_DATABASE_TYPE == 'live' )         # set by initial file called from Apache document root
  define( 'CONSTANT_DATABASE_NAME', CONSTANT_LIVE_DATABASE_NAME );

elseif( CONSTANT_DATABASE_TYPE == 'test' )
  define( 'CONSTANT_DATABASE_NAME', CONSTANT_TEST_DATABASE_NAME );

elseif( CONSTANT_DATABASE_TYPE == 'dev' )
  define( 'CONSTANT_DATABASE_NAME', CONSTANT_DEV_DATABASE_NAME );

else
  die( 'Invalid input detected in defines.php' );

?>
