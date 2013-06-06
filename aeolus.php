<?php
# Launch script for the basic Aeolus PHP framework
# Author: Sushila Burgess
#=======================================================================================
# Note: in a newly-installed, vanilla system, first login is with the following details:
# Username: aeolusa1supr
# Password: ChangeMeNowPlease!
#=======================================================================================

define( 'CFG_PREFIX', 'aeolus' );
define( 'CFG_SYSTEM_TITLE', 'Aeolus Database Query System' );

if( ! $database_type_set ) 
  define( 'CONSTANT_DATABASE_TYPE', 'live' );

if( ! $sourcedir_set )
  define( 'CONSTANT_SOURCEDIR', '/path/ending/in/slash/' ); # change this to required source code directory

$include_file = CONSTANT_SOURCEDIR . 'common_components.php';
require_once "$include_file";

$app = new Application_Entity;
$app->startup();

?>
