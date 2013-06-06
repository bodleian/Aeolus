<?php
# Launch script for the basic Aeolus PHP framework (development version)
# Author: Sushila Burgess
#=======================================================================================
# Note: in a newly-installed, vanilla system, first login is with the following details:
# Username: aeolusa1supr
# Password: ChangeMeNowPlease!
#=======================================================================================

define( 'CONSTANT_DATABASE_TYPE', 'dev' );
$database_type_set = TRUE;

define( 'CONSTANT_SOURCEDIR', '/path/ending/in/slash/' ); # change this to required source code directory
$sourcedir_set = TRUE;

require_once "aeolus.php";

?>
