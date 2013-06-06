<?php
/*
 * Setting of general constants, and of non-sensitive site-specific constants.
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */

define( 'AEOLUS_VERSION', '2.3' );
define( 'CONSTANT_AEOLUS_HOSTNAME', 'localhost:5432' );

#=============================
# Characters and HTML entities
#=============================
define( 'NEWLINE', "\n" );
define( 'CARRIAGE_RETURN', "\r" );
define( 'LINEBREAK', "<br />\n" );
define( 'SPACE', ' &nbsp; ' );
define( 'AMPERSAND', ' &amp; ' );
define( 'PERCENT_SIGN', '%' );

#======================
# Querying the database
#======================
# Constants used mainly in class DBQuery
define('ROWCOUNT_NOT_SET', -1);    # Set to -1 to avoid confusion with real rowcounts of 0.
define('CURR_ROWNO_NOT_SET', 0);   # When set starts from 1, i.e. first row retrieved is 1.
define('CURR_COLNO_NOT_SET', -1);  # When set starts from 0, as row is an array so first column in row is 0.
define( 'WRITE_DB_LOGFILE_FOR_BATCH_LOAD', FALSE );
define( 'DEBUG_DEV_SQL', FALSE );

# Constants used mainly in class DBEntity 
define( 'DEFAULT_RECORD_LAYOUT', 'across_page' );  # for use when outputting one page of data
define( 'DEFAULT_ENTRIES_PER_BROWSE_PAGE', 20 );
define( 'MAX_ENTRIES_PER_BROWSE_PAGE', 100 );
define( 'DEFAULT_PAGE_SELECTION_BUTTONS', 10 );
define( 'DEFAULT_PAGE_SELECTION_BUTTONS_FOR_NARROW_PAGE', 6 );
define( 'MAX_PAGE_SELECTION_BUTTON_ROWS', 5 );  # how many rows of page selection buttons to allow
define( 'DEFAULT_DESCENDING', 0 );
define( 'DEFAULT_TEXT_SEARCH_OP', 'contains' );
define( 'DEFAULT_NUMERIC_SEARCH_OP', 'equals' );
define( 'DEFAULT_DATE_SEARCH_OP', 'equals' );
define( 'DEFAULT_NULL_REPLACEMENT_DATE_EARLY', "to_date('21-JUN-4712 BC', '01-MON-YYYY BC')" );
define( 'DEFAULT_NULL_REPLACEMENT_DATE_LATE',  "to_date('31-DEC-9999', '01-MON-YYYY')" );
define( 'DEFAULT_NULL_REPLACEMENT_INT', '-99999999' );
define( 'LARGE_SEARCHABLE_FIELD_LIST', 15 );

#==============
# Lookup tables
#==============
define( 'MAX_LOOKUP_USES_DISPLAYED', 100 );

#============
# Help system
#============
define( 'HELP_EDIT_ROWS', 28 );
define( 'HELP_EDIT_COLS', 100 );
define( 'HELP_VIEW_ROWS_ESTIMATE', 42 );
define( 'HELP_VIEW_COLS_ESTIMATE', 150 );
define( 'PRODUCE_MANUAL_FROM_COMMANDLINE', 'produce_manual.php' );
define( 'PRODUCE_ONE_HELP_FILE_FROM_COMMANDLINE', 'produce_one_help_file.php' );

#=======================
# Emailing query results
#=======================
define( 'NO_REPLY_EMAIL_SENDER', '<noreply@bdlss.ox.ac.uk>' );
define( 'MAX_ROWS_IN_CSV_FILE', 2000 );
define( 'MAX_CSV_ROWS_FOR_PUBLIC_USER', 100 );

#====================================================================================================
# Users and login, originally for use on a non-secure server. Login checks were based on the 'Client 
# Handshake Protocol' method where a token is passed from server to client, and then the username and
# password are joined to the token and encrypted all together before being passed back to the server.
# This encryption is obsolete if the system is running on a secure server, but the code has remained
# in place because it is easier to leave it there than to take it out.
#====================================================================================================
define( 'LOGIN_REQUEST',  'RequestOULSLogin' );
define( 'RAW_USERNAME_FIELD', 'raw_usr' );
define( 'RAW_PASSWORD_FIELD', 'raw_passwd' );
define( 'MD5_USERNAME_FIELD', 'md5_usr' );
define( 'MD5_PASSWORD_FIELD', 'md5_passwd' );
define( 'MD5_LENGTH', 32 );
define( 'LOGIN_TOKEN_FIELD', 'logintoken' );
define( 'SESSION_TOKEN_FIELD', 'sesstoken' );

define( 'SESSION_OK', 1 );
define( 'SESSION_TIMED_OUT', -1 );
define( 'SESSION_NOT_FOUND', -2 );

define( 'GENERIC_ERR_MSG', 'Cannot log you in due to technical problems. Please seek advice from support staff.' ); 

define( 'LOGIN_SUCCESS', 1 );
define( 'LOGIN_FAILED_BAD_USR_OR_PW', 0 );
define( 'LOGIN_FAILED_ACCOUNT_SUSPENDED', -1 );

# Fields/variables used in setting up new users and changing passwords
# Form fields
define( 'RAW_AUTHORISING_NAME_FIELD', 'raanf' );
define( 'RAW_AUTHORISING_PASSFIELD',  'raapf' );
define( 'MD5_AUTHORISING_NAME_FIELD', 'mfanf' );
define( 'MD5_AUTHORISING_PASSFIELD',  'mfapf' );
define( 'AUTHORISING_TOKEN_FIELD',    'atfie' );
define( 'RAW_EDITED_NAME_FIELD',      'raenf' );
define( 'RAW_EDITED_PASSFIELD1',      'repfo' );
define( 'RAW_EDITED_PASSFIELD2',      'repft' );
define( 'MD5_EDITED_NAME_FIELD',      'mfenf' );
define( 'MD5_EDITED_PASSFIELD1',      'mepfo' );
define( 'MD5_EDITED_PASSFIELD2',      'mepft' );

# Javascript variable names
define( 'RAW_AUTHORISING_NAME_VAR', 'raanv' );
define( 'RAW_AUTHORISING_PASS_VAR', 'raapv' );
define( 'MD5_AUTHORISING_NAME_VAR', 'mfanv' );
define( 'MD5_AUTHORISING_PASS_VAR', 'mfapv' );
define( 'AUTHORISE_TOKEN_VAR',      'autva' );
define( 'RAW_EDITED_NAME_VAR',      'raenv' );
define( 'RAW_EDITED_PASSVAR1',      'repvo' );
define( 'RAW_EDITED_PASSVAR2',      'repvt' );
define( 'MD5_EDITED_NAME_VAR',      'mfenv' );
define( 'MD5_EDITED_PASSVAR1',      'mepvo' );
define( 'MD5_EDITED_PASSVAR2',      'mepvt' );
# End of fields/variables used in setting up new users and changing passwords

# Prefix for fields picked up from GET which should then be passed as parameters to menu
define( 'GET_RECORD_AFTER_LOGIN_PREFIX', 'get_after_login_' );

#========
# Colours
#========
# The following colours are defined in the University branding toolkit, and are the colours used
# when the system is run in 'standalone' mode, i.e. not under the CMS.
define( 'OXFORD_BLUE',  '#002147' );
define( 'PASTEL_BLUE',  '#c5d2e0' );
define( 'PASTEL_OLIVE', '#e1deae' );
define( 'MID_GREEN'   , '#7ca295' );
define( 'DARK_RED'    , '#822433' );

# Define a set of alternative colours (also from branding toolkit) 
# for possible use in test/training database
define( 'PASTEL_PINK',  '#e9c5cb' );
define( 'PASTEL_BROWN',  '#cac0b6' );
define( 'PASTEL_TAN',  '#f1e3bb' );
define( 'PASTEL_GREEN',  '#bec5c2' );
define( 'MID_BROWN',  '#ab8876' );
define( 'MID_BLUE',  '#4b92db' );
define( 'DARK_GREEN',  '#005751' );

#==============
# Screen layout
#==============
define('PAGE_TOP', 'aeolus_page_top_anchor');
define('PAGE_BOTTOM', 'aeolus_page_bottom_anchor');
define( 'DEFAULT_COL1_FIELD_LABEL_WIDTH_PX', 130 );
define( 'DEFAULT_COL1_FIELD_VALUE_POS_PX', 140 );
define( 'DEFAULT_COL2_FIELD_LABEL_WIDTH_PX', 520 );
define( 'DEFAULT_COL2_FIELD_VALUE_POS_PX', 540 );

#=============================
# Selecting from popup windows
#=============================
define( 'APP_POPUP_SELECTFORM_NAME', 'app_popup_selectform' );
define( 'POPUP_WINDOW_HEIGHT', 800 );
define( 'POPUP_WINDOW_WIDTH', 700 );

#=======================
# Bodleian Libraries CMS
#=======================
define( 'CMS_POPUP_WINDOW_WIDTH', 800 ); # Setting when Aeolus is deployed as Remote Content page in our CMS
define( 'CMS_DEPLOYMENT_START_TAG', '<!-- Start Aeolus Database Query System 2 -->' );
define( 'CMS_DEPLOYMENT_END_TAG',   '<!-- End Aeolus Database Query System 2 -->' );

# If deploying a screen under Bodleian Libraries CMS (which does not use standard branded colours),
# replace the standard colours with the ones used in the CMS.
define( 'CMS_LILAC', '#bcbce3' );
define( 'CMS_WEDGWOOD_BLUE', '#ccccff' );
define( 'CMS_INDIGO', '#3458a7' );
define( 'CMS_FONT_SIZE', '10pt' );

?>
