<?php
/*
 * PHP class for general database access
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */

require_once 'DB.php';

class DBQuery extends Application_Entity {

  #-----------------------------------------
  # Properties which start off non-blank
  #-----------------------------------------
  var $host = CONSTANT_AEOLUS_HOSTNAME;   # E.g. 'localhost:5432' - set by public_defines.php 
  var $db_name = CONSTANT_DATABASE_NAME;  # Set conditionally by defines.php from private_defines.php 

  #-----------------------------------------
  # Properties which start off blank
  #-----------------------------------------
  var $user             = NULL;
  var $pass             = NULL;
  var $dsn              = NULL;
  var $connection       = NULL;
  var $statement        = NULL;
  var $query_is_select  = NULL;
  var $dataset_obj      = NULL;
  var $dataset_array    = NULL;
  var $rowcount         = NULL;
  var $curr_row_array   = NULL;
  var $cols_in_curr_row = NULL;
  var $curr_colno       = NULL;
  var $curr_col_value   = NULL;
  var $curr_col_ident   = NULL;

  #-----------------------------------------------------

  function DBQuery( $user = '' ) {  # Connect to the default database as soon as the object is created.

    if( trim( $user ) == '' ) die("User name must be entered. \n");

    $this->user = $user;

    $this->dsn = "pgsql://$this->user@$this->host/$this->db_name";

    $this->db_connect();

    $this->pm_clear_dataset();  # set default values 
  }
  #-----------------------------------------------------

  function db_connect() {

    $this->connection = DB::connect( $this->dsn );
    $this->pm_die_if_error( 'connection' );
  }

  #-----------------------------------------------------

  function db_disconnect() {
    $this->connection->disconnect();
  }

  #-----------------------------------------------------

  # Get whole dataset (object) returned by select statement, 
  # could be multiple rows. This can also be used for updates etc.
  # Any column values containing single quotes MUST have been pre-escaped:
  # VALID $statement  : select * from jj_barcodes where barcode = '*7000000018*'
  # INVALID $statement: select * from jj_barcodes where created_by = 'Jasmine O'Donnell'
  # VALID $statement  : select * from jj_barcodes where created_by = 'Jasmine O\'Donnell'

  # November 2014: we want to avoid using backslashes to escape SQL statements.
  # Convert backslash plus single quote to two single quotes, and remove any backslashes before double quotes.
  # See new function pm_generic_escape_to_sql_escape().

  function db_run_query( $statement ) {

    $statement = $this->pm_generic_escape_to_sql_escape( $statement );

    $this->pm_clear_dataset();
    $this->pm_store_statement( $statement );
    $this->pm_debug_statement( $statement );
    if( ! $this->query_is_select ) $this->pm_write_logfile( $statement );

    $this->dataset_obj = $this->connection->query( $statement );
    $this->pm_die_if_error( 'dataset_obj' );

    if( is_object( $this->dataset_obj ))  # updates/inserts/deletes seem to return an integer not an object
      $this->rowcount = $this->dataset_obj->numrows();
    else
      $this->rowcount = ROWCOUNT_NOT_SET;

    return $this->dataset_obj;
  }

  #-----------------------------------------------------
  # Return value of first column in first row retrieved by query.

  function db_select_one_value( $statement ) {

    $this->db_run_query( $statement );

    if( $this->db_fetch_next_row( DB_FETCHMODE_ORDERED ) ) $this->db_fetch_next_col();

    return $this->curr_col_value;
  }

  #-----------------------------------------------------
  # Normally query returns an object. 
  # Put data from this object into an array, and return that instead

  function db_select_into_array( $statement ) {

    $this->db_run_query( $statement );

    if( ! is_object( $this->dataset_obj )) return NULL;

    while( $this->db_fetch_next_row() ) {
      $this->dataset_array[] = $this->curr_row_array;
    }

    $this->dataset_obj->free(); # We don't need this any more as we have all the same data in an array

    return $this->dataset_array;
  }

  #-----------------------------------------------------
  # Run an insert, update or delete statement
  # and return rowcount. If you don't need rowcount,
  # it will be more efficient to use "db_run_query" instead.

  function db_exec_returning_rowcount( $statement ) {

    $this->pm_clear_dataset();
    $this->pm_store_statement( $statement );
    $this->pm_write_logfile( $statement );

    if( $this->query_is_select ) {  # Use normal query method, which sets rowcount for selects.
      $this->db_run_query( $statement );
    }
    else { # Execute an insert, update or delete and return rowcount.

      $statement = addslashes( $statement );
      $statement = $this->pm_generic_escape_to_sql_escape( $statement );

      $statement = "select dbf_exec_with_rowcount(  '$statement'  )";

      $this->rowcount = $this->db_select_one_value( $statement ) ;
    }
    return $this->rowcount;
  }

  #-----------------------------------------------------
  # Get the next row of the dataset

  function db_fetch_next_row( $fetch_mode = DB_FETCHMODE_ASSOC ) {

    $this->pm_clear_cols();
    if( ! is_object( $this->dataset_obj )) return NULL;

    $this->curr_row_array = $this->dataset_obj->fetchRow( $fetch_mode );
    $this->pm_die_if_error( 'curr_row_array' );

    if( is_array( $this->curr_row_array )) {  # Some data was retrieved
      $this->curr_rowno++;
      $this->cols_in_curr_row = count( $this->curr_row_array );
    }

    return $this->curr_row_array;
  }
  #-----------------------------------------------------

  function db_fetch_next_col() {

    if( $this->cols_in_curr_row >= 1 
    &&  $this->curr_colno < $this->cols_in_curr_row - 1 ) {
      $this->curr_colno++;

      # The keys of a row array can be either numerical or associative (column names)
      # Either the column name or number will go into property "curr_col_ident".

      if( key_exists( $this->curr_colno, $this->curr_row_array )) {  # numerical keys
        $this->curr_col_value = $this->curr_row_array[ $this->curr_colno ];
        $this->curr_col_ident = $this->curr_colno;
      }

      else {  # column name keys
        $colcount = CURR_COLNO_NOT_SET;
        foreach( $this->curr_row_array as $colname => $col_value ) {
          $colcount++;
          if( $colcount == $this->curr_colno ) {
            $this->curr_col_value = $col_value;
            $this->curr_col_ident = $colname;
            break;
          }
        }
      }
    }
    else {
      $this->curr_col_value = NULL;
      $this->curr_col_ident = NULL;
    }

    return $this->curr_col_value;
  }
  #--------------------------------------------------------------------------

  function db_get_curr_col_value() {
    return $this->curr_col_value;
  }
  #--------------------------------------------------------------------------

  function db_get_curr_col_ident() {
    return $this->curr_col_ident;
  }
  #--------------------------------------------------------------------------

  ###########################################################################
  #--------------------------------------------------------------------------
  #----------- The following should be treated as PRIVATE methods -----------
  #----------- The prefix "pm" indicates PRIVATE METHOD. --------------------
  #--------------------------------------------------------------------------

  function pm_die_if_error( $database_object ) {

    $newline = "\n";

    if( DB::isError( $this->$database_object ) ) {

      echo $newline . '<br><br>' . $newline;

      if( $this->debug ) {
        echo 'Last recorded statement: ' . $this->statement;
        echo $newline . '<br><br>' . $newline;
        die ( $this->$database_object->getMessage() . '<br>' . $newline );
      }
      else # try not to give a potential hacker any useful info
        die ( 'An error has occurred.' . '<br>' . $newline );
    }
  }

  #-----------------------------------------------------

  function pm_clear_cols() {

    $this->cols_in_curr_row = NULL;
    $this->curr_col_value   = NULL;
    $this->curr_colno       = CURR_COLNO_NOT_SET;
  }
  #-----------------------------------------------------

  function pm_clear_dataset() {

    $this->dataset_obj  = NULL;
    $this->dataset_array  = NULL;
    $this->curr_row_array = NULL;

    $this->rowcount = ROWCOUNT_NOT_SET;
    $this->curr_rowno = CURR_ROWNO_NOT_SET;

    $this->pm_clear_cols();
  }
  #-----------------------------------------------------

  function pm_store_statement( $statement ) {

    $this->statement = $statement;  # Record the current statement for future reference
                                    # and see whether or not it is a select statement.
    $this->query_is_select = FALSE;

    $statement = strtolower( ltrim( $this->statement ));

    if( strlen( $statement ) > strlen('select')) {
      if( substr( $statement, 0, strlen('select')) == 'select' )
        $this->query_is_select = TRUE;
    }
  }
  #-----------------------------------------------------

  function pm_debug_statement( $statement ) {

    if( CONSTANT_DATABASE_TYPE == 'dev' && DEBUG_DEV_SQL && $this->debug ) {
      echo NEWLINE . '<!-----Start SQL Debug------' . NEWLINE;
      echo $statement . NEWLINE;
      echo '-----End SQL Debug------>' . NEWLINE;
    }
  }
  #-----------------------------------------------------

  function pm_write_logfile( $statement ) {

    $today = $this->get_date_now_yyyymmdd();

    $filename = DB_LOGFILE_DIR;  # Assume we are running this on the web, in which case
                                 # we can write to directory owned by Apache webserver

    $server_software = '';
    if( key_exists( 'SERVER_SOFTWARE', $_SERVER ))
      $server_software = $_SERVER['SERVER_SOFTWARE'];
    if( $server_software == '' ) {
      $filename = '';  # Running in batch mode from the Unix prompt 
                       # Use current directory for now
      if( WRITE_DB_LOGFILE_FOR_BATCH_LOAD == FALSE ) return;
    }
    elseif( CONSTANT_DATABASE_NAME != CONSTANT_LIVE_DATABASE_NAME )
      return;  # no need to log everything happening on test

    $filename = $filename . $this->get_system_prefix() . '_' . $this->db_name . '_' . $today . '.log';

    $attempts_to_make = 5; # number of times to try opening the logfile

    # Prepare the string to write to the logfile
    $datetime_now_in_words = $this->get_datetime_now_in_words();
    $statement = '/* ' . $this->user . ' ' . $datetime_now_in_words . ' */ ' . $statement;
    $statement = $statement . NEWLINE . '\p\g' . NEWLINE;
    
    # Open the logfile
    $handle = fopen( $filename, 'a' );
    $attempts = 0;
    while( $attempts < $attempts_to_make && ! $handle ) {
      sleep( 1 );
      $handle = fopen( $filename, 'a' );
      $attempts++;
    }

    # Die if you cannot open the logfile
    if( ! $handle ) {
      if( ! $this->debug ) $filename = '';
      die( 'Unable to open log file ' . $filename );
    }

    $result = fwrite( $handle, $statement );  # returns number of bytes written
    if( $result < strlen($statement)) die( 'Error writing logfile' );

    fclose( $handle );
  }
  #-----------------------------------------------------
  function pm_generic_escape_to_sql_escape( $statement ) {

    # November 2014: we want to avoid using backslashes to escape SQL statements.
    # Convert backslash plus single quote to two single quotes, and remove any backslashes before double quotes.

    #echo 'BEFORE: ' . $statement . LINEBREAK;

    $statement = str_replace( "\\'", "''", $statement );
    $statement = str_replace( '\\"', '"', $statement );

    #echo 'AFTER: ' . $statement . LINEBREAK;

    return $statement;
  }
  #-----------------------------------------------------
}
?>
