<?php
/*
 * PHP parent class for all classes deployed on the web. Basically everything.
 * May 2013: now has a parent class itself: Infrastructure (for site-specific settings and methods).
 *
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */

class Application_Entity extends Infrastructure {

  #------------
  # Properties 
  #------------
  var $debug = FALSE;

  var $publicly_available_page;
  var $username               ;
  var $person_name            ;
  var $menu_item_id           ;
  var $menu_item_name         ;
  var $menu_parent_id         ;
  var $menu_class_name        ;
  var $menu_method_name       ;
  var $menu_user_restriction  ;
  var $menu_hidden_parent     ;
  var $menu_called_as_popup   ;
  var $has_csv_option         ;
  var $has_printable_option   ;
  var $csv_output             ;
  var $printable_output       ;

  #-----------------------------------------------------

  function Application_Entity() { 

    # Set up inheritance from parent class
    $this->Infrastructure();
  }
  #-----------------------------------------------------

  function app_get_declared_classes() { 

    $classes = get_declared_classes();

    if( is_array( $classes )) {
      for( $i = 0; $i < count( $classes ); $i++ ) {
        $mixed_case = $classes[ $i ];
        $classes[ $i ] = strtolower( $mixed_case );
      }
      return $classes;
    }

    return array();
  }
  #-----------------------------------------------------

  function app_get_class( $obj ) { 

    if( is_object( $obj )) {
      $obj_class = get_class( $obj );
      return strtolower( $obj_class );
    }
    return NULL;
  }
  #-----------------------------------------------------

  function app_get_class_methods( $classname ) {

    $methods = get_class_methods( $classname );

    if( is_array( $methods )) {
      for( $i = 0; $i < count( $methods ); $i++ ) {
        $mixed_case = $methods[ $i ];
        $methods[ $i ] = strtolower( $mixed_case );
      }
      return $methods;
    }

    return array();
  }
  #-----------------------------------------------------

  function aeolus_version_info() {

    echo 'Aeolus Database Query System ' . AEOLUS_VERSION . ' ';
    if( $this->publicly_available_page )
      echo LINEBREAK;
    else {
      html::bullet_point();
      echo ' ';
    }
    echo 'Answers to Enquiries from Oxford Library Users and Staff '; 
    if( $this->publicly_available_page )
      echo LINEBREAK;
    else {
      html::bullet_point();
      echo ' ';
    }
    echo '&copy; Bodleian Libraries 2008-2013';
  }
  #-----------------------------------------------------

  function clear() { # Clear all properties except DB connection, menu/login info and debug setting.

    $properties = get_object_vars( $this );

    # Properties which will not be cleared
    $keep_debug = $this->debug;
    $keep_publicly_available_page = $this->publicly_available_page;

    $keep_username              = $this->username              ;
    $keep_person_name           = $this->person_name           ;

    $keep_menu_item_id          = $this->menu_item_id          ;
    $keep_menu_item_name        = $this->menu_item_name        ;
    $keep_menu_parent_id        = $this->menu_parent_id        ;
    $keep_menu_class_name       = $this->menu_class_name       ;
    $keep_menu_method_name      = $this->menu_method_name      ;
    $keep_menu_user_restriction = $this->menu_user_restriction ;
    $keep_menu_hidden_parent    = $this->menu_hidden_parent    ;
    $keep_menu_called_as_popup  = $this->menu_called_as_popup  ;

    $keep_has_csv_option        = $this->has_csv_option;
    $keep_has_printable_option  = $this->has_printable_option;
    $keep_csv_output            = $this->csv_output;
    $keep_printable_output      = $this->printable_output;

    if( is_array( $properties )) {
      foreach( $properties as $property_name => $property ) {

        if( $property_name != 'db_connection' ) { # Never clear database connection

          if( is_object( $this->$property_name )) {
            if( method_exists( $this->$property_name, 'clear' ) )
              $this->$property_name->clear();
            else
              $this->$property_name = NULL;
            }
            else 
              $this->$property_name = NULL;
        }
      }
    }

    $this->debug = $keep_debug;
    $this->publicly_available_page = $keep_publicly_available_page;

    $this->username              = $keep_username              ;
    $this->person_name           = $keep_person_name           ;

    $this->menu_item_id          = $keep_menu_item_id          ;
    $this->menu_item_name        = $keep_menu_item_name        ;
    $this->menu_parent_id        = $keep_menu_parent_id        ;
    $this->menu_class_name       = $keep_menu_class_name       ;
    $this->menu_method_name      = $keep_menu_method_name      ;
    $this->menu_user_restriction = $keep_menu_user_restriction ;
    $this->menu_hidden_parent    = $keep_menu_hidden_parent    ;
    $this->menu_called_as_popup  = $keep_menu_called_as_popup  ;

    $this->has_csv_option        = $keep_has_csv_option;
    $this->has_printable_option  = $keep_has_printable_option;
    $this->csv_output            = $keep_csv_output;
    $this->printable_output      = $keep_printable_output;
  }
  #-----------------------------------------------------

  function startup() {

    $logout = $this->read_get_parm( 'logout' );
    if( $logout ) $this->logout();

    $this->expire_page();
    $this->set_character_encoding();

    #---------------
    # Get parameters
    #---------------
    $menu_item_id = $this->read_get_parm( 'menu_item_id' );

    if( ! $menu_item_id ) $menu_item_id = $this->read_post_parm( 'menu_item_id' );

    $class_name  = $this->read_post_parm( 'class_name' );
    $method_name = $this->read_post_parm( 'method_name' );

    if( ! $menu_item_id && ! $class_name && count( $_GET ) > 0 && count( $_POST ) == 0 ) {
      $get_class_name  = $this->read_get_parm( 'class_name' );
      $get_method_name = $this->read_get_parm( 'method_name' );
      if( $get_class_name && $get_method_name && $this->is_search_results_method( $get_method_name )) {
        $class_name = $get_class_name;
        $method_name = $get_method_name;
      }
    }

    #--------------------------
    # Start/restart PHP session
    #--------------------------
    session_start();

    #--------------------------------------
    # Log in, or get existing login details
    #--------------------------------------
    $db_connection = new DBQuery ( CONSTANT_MINIMAL_USER );
    $the_user = $this->app_get_user_object_for_login( $db_connection );
    $the_user->login();
    if( ! $the_user->is_logged_in() ) die();

    #-----------------
    # Display the menu
    #-----------------
    $this->app_run_menu( $the_user, $menu_item_id, $class_name, $method_name  );
  }
  #-----------------------------------------------------

  function app_get_user_object_for_login( &$db_connection ) { # this can be overridden in child class
    $the_user = new User( $db_connection );                   # e.g. in the data collection tool
    return $the_user;
  }
  #-----------------------------------------------------

  function app_run_menu( $the_user, $menu_item_id, $class_name, $method_name  ) { 

    # Can be overridden in child class e.g. in the data collection tool
    $menu = new Menu( $the_user->db_connection, $menu_item_id, $class_name, $method_name );
    $menu->run_menu( $the_user->db_get_username(), 
                     $the_user->get_person_name(),
                     $the_user->get_login_time(),
                     $the_user->get_prev_login()
                   );
  }
  #-----------------------------------------------------

  function search_startup( $class_name ) {  # Goes straight into DB search. No logging in required.

    if( ! $class_name ) die( 'Invalid startup parameter.' );
    $this->publicly_available_page = TRUE;
    $this->debug = FALSE; # This method is available to the general public, i.e. without logging in, so be cautious.

    $db_connection = new DBQuery ( CMS_DB_USERNAME );  # a readonly user
    $db_connection->debug = FALSE;

    $searchclass = new $class_name( $db_connection );
    $searchclass->debug = FALSE;

    $searchclass->publicly_available_page = $this->publicly_available_page;
    $table_name = $searchclass->get_public_search_table(); # If the class doesn't have this method, script will die.

    echo CMS_DEPLOYMENT_START_TAG . NEWLINE;
    html::write_stylesheet( $this->publicly_available_page );
    html::page_top_anchor();
    echo LINEBREAK;

    $method_name = $searchclass->read_post_parm( 'method_name' );

    if( ! $method_name || $method_name == 'db_search' )
      $searchclass->db_search( $table_name );

    else {  # We will only allow methods of a 'safe' type, not data-entry methods
      if( $this->is_search_results_method( $method_name ))
        $searchclass->$method_name();
    }

    echo LINEBREAK;
    html::horizontal_rule();
    html::page_bottom_anchor();
    $this->aeolus_version_info();
    echo LINEBREAK;

    echo CMS_DEPLOYMENT_END_TAG . NEWLINE;
  }
  #-----------------------------------------------------

  function help() {

    $this->debug = FALSE; # This method is available without logging in, so be cautious.

    $db_connection = new DBQuery ( HELP_DB_USERNAME );  # a readonly user
    $db_connection->debug = FALSE;

    html::html_start();
    html::html_head_start();
    html::write_stylesheet( $for_cms_deployment = FALSE, $banner_only = TRUE );
    html::html_head_end();

    html::body_start();

    $help_obj = new Help( $db_connection );
    $help_obj->debug = FALSE;

    $help_obj->display_help();

    html::body_end();
  }
  #-----------------------------------------------------

  function logout() {

    $session_token = $this->read_get_parm(  SESSION_TOKEN_FIELD  );

    $funcname = DBEntity::db_database_function_name( 'delete_session' );

    $minimal_db_connection = new DBQuery ( CONSTANT_MINIMAL_USER );
    $minimal_db_connection->db_run_query( "select $funcname( '$session_token' )" );
    $minimal_db_connection = NULL;

    $this->write_get_parm( SESSION_TOKEN_FIELD,  '' );
    $this->write_post_parm( SESSION_TOKEN_FIELD, '' );

    $this->clear();
    session_start();
    session_destroy();
  }
  #-----------------------------------------------------

  function strip_all_slashes( $value_with_slashes ) {

    $interim_result = $value_with_slashes;
    $final_result = stripslashes( $value_with_slashes );
    $attempts = 0;

    while( $interim_result != $final_result && $attempts < 20 ) {
      $attempts++;
      $interim_result = stripslashes( $interim_result );
      $final_result = stripslashes( $final_result );
    }

    return $final_result;
  }
  #-----------------------------------------------------

  function stripslashes_conditional ( $original_value ) {

    # 'Magic quotes' deprecated as of PHP 5.3 and removed in 5.4
    #if( $this->magic_quotes_gpc_on )
      #return stripslashes( $original_value );
    #else
      return $original_value;
  }
  #-----------------------------------------------------

  function escape( $the_value ) {

    $the_value = $this->strip_all_slashes( $the_value );

    $the_value = addslashes( $the_value );

    return $the_value;
    
  }
  #-----------------------------------------------------

  function get_datetime_now_in_words( $include_seconds = FALSE ) {

    $dt = getdate();
    $display_str = "$dt[mday] $dt[month] $dt[year] $dt[hours]:";
    if( $dt['minutes'] < 10) $display_str = $display_str . '0';
    $display_str = $display_str . $dt['minutes'];
    if( $include_seconds ) {
      $display_str = $display_str . ':';
      if( $dt['seconds'] < 10) $display_str = $display_str . '0';
      $display_str = $display_str . $dt['seconds'];
    }
    return $display_str;
  }
  #-----------------------------------------------------

  function get_date_now_in_words() {

    $dt = getdate();
    $display_str = "$dt[mday] $dt[month] $dt[year]";
    return $display_str;
  }
  #-----------------------------------------------------

  function get_date_now_yyyymmdd() {

    $dt = getdate();

    $year  = $dt['year'];
    $month = $dt['mon'];
    $day   = $dt['mday'];

    $display_str = "$year";

    if( $month < 10 ) $display_str = $display_str . '0';
    $display_str = $display_str . $month;

    if( $day < 10 ) $display_str = $display_str . '0';
    $display_str = $display_str . $day;

    return $display_str;
  }
  #-----------------------------------------------------

  function get_date_now_dd_mm_yyyy() {

    $dt = getdate();

    $year  = $dt['year'];
    $month = $dt['mon'];
    $day   = $dt['mday'];

    $display_str = '';

    if( $day < 10 ) $display_str = $display_str . '0';
    $display_str = $display_str . $day . '/';

    if( $month < 10 ) $display_str = $display_str . '0';
    $display_str = $display_str . $month . '/';

    $display_str = $display_str . $year;

    return $display_str;
  }
  #-----------------------------------------------------

  function get_postgres_datetime( $the_datetime = NULL ) {

    if( $the_datetime == NULL ) $the_datetime = getdate();
    
    $year  = $the_datetime['year'];
    $month = $the_datetime['mon'];
    $day   = $the_datetime['mday'];

    $display_str = "$year-";

    if( $month < 10 ) $display_str = $display_str . '0';
    $display_str = $display_str . $month . '-';

    if( $day < 10 ) $display_str = $display_str . '0';
    $display_str = $display_str . $day . ' ';

    if( $the_datetime['hours'] < 10) $display_str = $display_str . '0';
    $display_str = $display_str . $the_datetime['hours'] . ':';

    if( $the_datetime['minutes'] < 10) $display_str = $display_str . '0';
    $display_str = $display_str . $the_datetime['minutes'] . ':';

    if( $the_datetime['seconds'] < 10) $display_str = $display_str . '0';
    $display_str = $display_str . $the_datetime['seconds'];

    return $display_str;
  } 
  #-----------------------------------------------------

  function postgres_date_to_dd_mm_yyyy( $the_datetime = NULL ) {  # Dates come out of database as 'yyyy-mm-dd'
                                                                  # Convert to 'dd/mm/yyyy'

    if( $the_datetime == NULL ) return NULL;

    $year    = strtok( $the_datetime, '-' );
    $month   = strtok( '-' );
    $day     = strtok( ' ' );
    $hours   = strtok( ':' );
    $minutes = strtok( ':' );

    $output_date = $day . '/' . $month . '/' . $year;

    if( $hours && $minutes ) 
      $output_date = $output_date . ' ' . $hours . ':' . $minutes;
    
    return $output_date;
  }
  #-----------------------------------------------------

  function dd_mm_yyyy_to_words( $the_datetime = NULL ) {  # Convert 'dd/mm/yyyy' to 'dd Mon yyyy'

    $parts = explode( '/', $the_datetime );
    $day   = $parts[0];
    $month = $parts[1];
    $year  = $parts[2];

    $time = '';
    $time_pos = strpos( $day, ' ' );
    if( $time_pos > 0 ) {
      $time = substr( $day, $time_pos );
      $day = substr( $day, 0, $time_pos );
    }

    $month = $this->get_month_name_from_number( $month );
    return $day . ' ' . $month . ' ' . $year . $time;
  }
  #-----------------------------------------------------

  function postgres_date_to_words( $the_datetime = NULL ) {
    $the_datetime = $this->postgres_date_to_dd_mm_yyyy( $the_datetime );
    $the_datetime = $this->dd_mm_yyyy_to_words( $the_datetime );
    return $the_datetime;
  }
  #-----------------------------------------------------

  function expire_page() {  # must be called before any HTML is output

    header( 'Expires: Thu, 08 Mar 2001 18:03:00 GMT' ); # date is in past, so page is automatically expired.
  }
  #-----------------------------------------------------

  function get_character_encoding() {
    return 'UTF-8';
  }
  #-----------------------------------------------------

  function set_character_encoding() {  # must be called before any HTML is output

    header( 'Content-Type: text/html; charset=' . $this->get_character_encoding() );
  }
  #-----------------------------------------------------

  function read_post_parm( $parm_name ) {

    return $this->read_parm( $parm_name, '$_POST' );
  }
  #-----------------------------------------------------

  function read_get_parm( $parm_name ) {

    return $this->read_parm( $parm_name, '$_GET' );
  }
  #-----------------------------------------------------

  function read_session_parm( $parm_name ) {

    return $this->read_parm( $parm_name, '$_SESSION' );
  }
  #-----------------------------------------------------

  function read_parm( $parm_name, $arrayname ) {

    if( $arrayname == '$_POST' )
      $this->parm_value = $_POST[ "$parm_name" ];

    elseif( $arrayname == '$_GET' )
      $this->parm_value = $_GET[ "$parm_name" ];

    elseif( $arrayname == '$_SESSION' ) {
      if( isset( $_SESSION )) 
        $this->parm_value = $_SESSION[ "$parm_name" ];
      else
        $this->parm_value = NULL;
    }

    else {
      if( $this->debug ) echo 'Read parameter=' . $parm_name . ' Array=' . $arrayname . LINEBREAK;
      die( 'Invalid input' );
    }

    $valid_parm = $this->validate_parm( $parm_name );

    if( ! $valid_parm ) {
      if( $this->continue_on_read_parm_err ) {
        #---------------------------------------------------------------------------------------
        # Even if we are allowing them to continue on data entry error (i.e. don't wipe out
        # their data entry because of an honest mistake like entering a date as "15/09/195"), 
        # ALWAYS die if we think there is malicious scripting in the input.
        #---------------------------------------------------------------------------------------
        if( ! $this->is_ok_free_text( $this->parm_value ) && ! $this->is_array_of_ok_free_text( $this->parm_value ))
          die( 'Invalid input' );

        $this->failed_validation = TRUE;
        if( ! $this->app_errmsgs ) $this->app_errmsgs = array();
        $errmsg = 'Invalid data format';
        $this->app_errmsgs[ "$parm_name" ] = $errmsg;
        if( ! $this->suppress_read_parm_errmsgs ) $this->display_errmsg( $parm_name, $errmsg );
      }
      else {
        if( $this->debug ) {
          echo 'Parameter name=' . $parm_name . LINEBREAK;
          $this->echo_safely( 'Value=' . $this->parm_value );
          echo LINEBREAK;
        }
        die( 'Invalid input' );
      }
    }

    return $this->parm_value;
  }
  #-----------------------------------------------------

  function write_post_parm( $parm_name, $parm_value ) {

    $this->write_parm( $parm_name, $parm_value, '$_POST' );
  }
  #-----------------------------------------------------

  function write_get_parm( $parm_name, $parm_value ) {

    $this->write_parm( $parm_name, $parm_value, '$_GET' );
  }
  #-----------------------------------------------------

  function write_session_parm( $parm_name, $parm_value ) {

    $this->write_parm( $parm_name, $parm_value, '$_SESSION' );
  }
  #-----------------------------------------------------

  function write_parm( $parm_name, $parm_value, $arrayname ) {

    if( $arrayname == '$_POST' )
      $_POST[ "$parm_name" ] = $parm_value;

    elseif( $arrayname == '$_GET' )
      $_GET[ "$parm_name" ] = $parm_value;

    elseif( $arrayname == '$_SESSION' )
      $_SESSION[ "$parm_name" ] = $parm_value;

    else {
      if( $this->debug ) echo 'Write parameter=' . $parm_name . ' Array=' . $arrayname . LINEBREAK;
      die( 'Invalid input' );
    }
  }
  #-----------------------------------------------------

  function unset_post_parm( $parm_name ) {

    $this->unset_parm( $parm_name, '$_POST' );
  }
  #-----------------------------------------------------

  function unset_get_parm( $parm_name ) {

    $this->unset_parm( $parm_name, '$_GET' );
  }
  #-----------------------------------------------------

  function unset_session_parm( $parm_name ) {

    $this->unset_parm( $parm_name, '$_SESSION' );
  }
  #-----------------------------------------------------

  function unset_parm( $parm_name, $arrayname ) {

    if( $arrayname == '$_POST' )
      unset( $_POST[ "$parm_name" ] );

    elseif( $arrayname == '$_GET' )
      unset( $_GET[ "$parm_name" ] );

    elseif( $arrayname == '$_SESSION' )
      unset( $_SESSION[ "$parm_name" ] );

    else {
      if( $this->debug ) echo 'Unset parameter=' . $parm_name . ' Array=' . $arrayname . LINEBREAK;
      die( 'Invalid input' );
    }
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # except on startup, this will be overridden by child classes

    switch( $parm_name ) {

      case 'menu_item_id':

        return $this->is_integer( $this->parm_value ); 

      case 'class_name':
      case 'method_name':
      case 'user_restriction':
      case 'menu_user_restriction':
      case 'calling_form':
      case 'calling_field':
      case 'opening_class':
      case 'opening_method':

        return $this->is_alphanumeric_or_blank( $this->parm_value, 
                                                $allow_underscores = TRUE, 
                                                $allow_all_whitespace = FALSE );

      case 'logout':
        return $this->is_on_off_switch( $this->parm_value );

      case SESSION_TOKEN_FIELD:
        return $this->is_md5( $this->parm_value );

      case 'user_roles':
        return $this->is_comma_separated_alphanumeric( str_replace( "'", '', $this->parm_value ));

      case 'user_role_ids':
        return $this->is_comma_separated_integers( $this->parm_value, $allow_negatives = TRUE );

      case 'user_email':
        return $this->is_email_address( $this->parm_value );

      default:
        return FALSE;
    }
    return FALSE;
  }
  #-----------------------------------------------------

  function is_integer( $parm_value, $allow_negative = FALSE, $allow_null = TRUE ) {

    $string_length = strlen( $parm_value );
    if( $string_length == 0 ) {
      if( $allow_null )
        return TRUE;
      else
        return FALSE;
    }

    for( $i = 0; $i < $string_length; $i++ ) {

      $ok = FALSE;
      $char = substr( $parm_value, $i, 1 );

      if(( $char >= '0' && $char <= '9' ) 
      || ( $allow_negative && $i == 0 && $char == '-' )) {

        $ok = TRUE;
      }
      if( ! $ok ) return FALSE;
    }
    return TRUE;
  }
  #-----------------------------------------------------

  function is_array_of_integers( $parm_value, $allow_negatives = FALSE ) {

    if( ! is_array( $parm_value )) return FALSE;

    foreach( $parm_value as $the_element ) {
      if( ! $this->is_integer( $the_element, $allow_negatives )) return FALSE;
    }

    return TRUE;
  }
  #-----------------------------------------------------

  function is_comma_separated_integers( $parm_value, $allow_negatives = FALSE ) {

    # Allow space after each comma
    $parm_value = str_replace( ' ', '', $parm_value );

    $parm_value = str_replace( ',', '', $parm_value );

    if( $allow_negatives ) $parm_value = str_replace( '-', '', $parm_value );

    return $this->is_integer( $parm_value );
  }
  #-----------------------------------------------------

  function is_numeric( $parm_value, $allow_negative = FALSE, $allow_null = TRUE ) {

    $parts_of_number = explode( '.', $parm_value );

    if( count( $parts_of_number ) > 2 ) return FALSE; # more than one decimal point

    return $this->is_integer( str_replace( '.', '', $parm_value ), $allow_negative, $allow_null );
  }
  #-----------------------------------------------------

  function is_array_of_numbers( $parm_value, $allow_negatives = FALSE ) {

    if( ! is_array( $parm_value )) return FALSE;

    foreach( $parm_value as $the_element ) {
      if( ! $this->is_numeric( $the_element, $allow_negatives )) return FALSE;
    }

    return TRUE;
  }
  #-----------------------------------------------------

  function is_alphanumeric( $parm_value, $allow_underscores = FALSE ) {

    $string_length = strlen( $parm_value );
    if( $string_length == 0 ) return FALSE;

    for( $i = 0; $i < $string_length; $i++ ) {

      $ok = FALSE;
      $char = substr( $parm_value, $i, 1 );

      if(( $char >= '0' && $char <= '9' ) 
      || ( $char >= 'a' && $char <= 'z' ) 
      || ( $char >= 'A' && $char <= 'Z' ) 
      || ( $allow_underscores && $char == '_' )) {

        $ok = TRUE;
      }
      if( ! $ok ) return FALSE;
    }
    return TRUE;
  }
  #-----------------------------------------------------

  function is_alphabetic( $parm_value ) {

    $string_length = strlen( $parm_value );
    if( $string_length == 0 ) return FALSE;

    for( $i = 0; $i < $string_length; $i++ ) {

      $ok = FALSE;
      $char = substr( $parm_value, $i, 1 );

      if(( $char >= 'a' && $char <= 'z' ) 
      || ( $char >= 'A' && $char <= 'Z' )) {

        $ok = TRUE;
      }
      if( ! $ok ) return FALSE;
    }
    return TRUE;
  }
  #-----------------------------------------------------

  function is_alphabetic_or_blank( $parm_value, $allow_all_whitespace = TRUE ) {

    if( $parm_value == '' ) return TRUE;

    if( $allow_all_whitespace && trim( $parm_value ) == '' ) return TRUE;

    return $this->is_alphabetic( $parm_value );
  }
  #-----------------------------------------------------

  function is_alphanumeric_or_blank( $parm_value, $allow_underscores = FALSE, $allow_all_whitespace = TRUE ) {

    if( $parm_value == '' ) return TRUE;

    if( $allow_all_whitespace && trim( $parm_value ) == '' ) return TRUE;

    return $this->is_alphanumeric( $parm_value, $allow_underscores );
  }
  #-----------------------------------------------------

  function is_comma_separated_alphanumeric( $parm_value, $allow_underscores = FALSE, $allow_all_whitespace = TRUE ) {

    # Allow space after each comma
    if( $allow_all_whitespace ) $parm_value = str_replace( ' ', '', $parm_value );

    $parm_value = str_replace( ',', '', $parm_value );

    if( $allow_all_whitespace )
      return $this->is_alphanumeric_or_blank( $parm_value, $allow_underscores );
    else
      return $this->is_alphanumeric( $parm_value, $allow_underscores );
  }
  #-----------------------------------------------------

  function is_alphanumeric_or_whitespace( $parm_value, $allow_underscores = FALSE ) {

    if( $parm_value == '' ) return TRUE;

    $parm_value = str_replace( ' ',    '', $parm_value );
    $parm_value = str_replace( "\n",   '', $parm_value );
    $parm_value = str_replace( "\t",   '', $parm_value );
    $parm_value = str_replace( "\r",   '', $parm_value );
    $parm_value = str_replace( "\0",   '', $parm_value );
    $parm_value = str_replace( "\x08", '', $parm_value ); # vertical tab

    return $this->is_alphanumeric( $parm_value, $allow_underscores );
  }
  #-----------------------------------------------------

  function is_md5( $parm_value, $allow_blank = TRUE ) {

    $string_length = strlen( $parm_value );
    if( $string_length == 0 ) {
      if( $allow_blank ) 
        return TRUE;
      else
        return FALSE;
    }

    if( $string_length != MD5_LENGTH ) return FALSE;

    if( strtolower( $parm_value ) != $parm_value ) return FALSE;  # contains uppercase letters

    return $this->is_alphanumeric( $parm_value, $allow_underscores = FALSE );
  }
  #-----------------------------------------------------

  function is_ok_free_text( $parm_value ) {

    if( $this->contains_scripting( $parm_value ))
      return FALSE;

    return TRUE;
  }
  #-----------------------------------------------------

  function is_array_of_ok_free_text( $parm_value ) {

    if( ! is_array( $parm_value )) return FALSE;

    foreach( $parm_value as $the_element ) {
      if( ! $this->is_ok_free_text( $the_element )) return FALSE;
    }

    return TRUE;
  }
  #-----------------------------------------------------

  function is_html_id( $parm_value ) {  # Starts with a letter.
                                        # Alphanumeric, plus can contain hyphen, underscore, colon or full stop. 

    $first_char = substr( $parm_value, 0, 1 );
    if( ! $this->is_alphabetic( $first_char )) return FALSE;

    $parm_value = str_replace( '-', '', $parm_value );
    $parm_value = str_replace( ':', '', $parm_value );
    $parm_value = str_replace( '.', '', $parm_value );

    return $this->is_alphanumeric( $parm_value, 
                                   $allow_underscores = TRUE, 
                                   $allow_all_whitespace = FALSE );
  }
  #-----------------------------------------------------

  function is_array_of_html_id( $parm_value ) {

    if( ! is_array( $parm_value )) return FALSE;

    foreach( $parm_value as $the_element ) {
      if( ! $this->is_html_id( $the_element )) return FALSE;
    }

    return TRUE;
  }
  #-----------------------------------------------------

  function is_comma_separated_html_id( $parm_value ) {

    if( ! is_string( $parm_value )) return FALSE;

    $trimmed = str_replace( ', ', ',', $parm_value );  # allow spaces after commas
    $elements = explode( ',', $trimmed );

    foreach( $elements as $the_element ) {
      if( ! $this->is_html_id( $the_element )) return FALSE;
    }

    return TRUE;
  }
  #-----------------------------------------------------

  function is_array_of_alphanumeric( $parm_value, $allow_underscores = FALSE  ) {

    if( ! is_array( $parm_value )) return FALSE;

    foreach( $parm_value as $the_element ) {
      if( ! $this->is_alphanumeric( $the_element, $allow_underscores )) return FALSE;
    }

    return TRUE;
  }
  #-----------------------------------------------------

  function is_array_of_alphanumeric_or_blank( $parm_value, $allow_underscores = FALSE, $allow_all_whitespace = TRUE  ) {

    if( ! is_array( $parm_value )) return FALSE;

    foreach( $parm_value as $the_element ) {
      if( ! $this->is_alphanumeric_or_blank( $the_element, $allow_underscores, $allow_all_whitespace )) return FALSE;
    }

    return TRUE;
  }
  #-----------------------------------------------------

  function is_dd_mm_yyyy( $parm_value, $allow_blank = TRUE, $allow_pre_1950 = TRUE, $allow_post_2050 = TRUE ) {

    if( $allow_blank && $parm_value == '' ) return TRUE;

    $separators = '/.-';

    $day = strtok( $parm_value,  $separators );
    $month = strtok( $separators );
    $year = strtok( $separators );

    if( ! $this->is_integer( $day ))   return FALSE;
    if( ! $this->is_integer( $month )) return FALSE;
    if( ! $this->is_integer( $year ))  return FALSE;

    if( $day < 1 || $day > 31 ) return FALSE;
    if( $month < 1  || $month > 12 ) return FALSE;

    switch( $month ) {
      case 4;
      case 6;
      case 9:
      case 11;
        if( $day > 30 ) return FALSE;
        break;

      case 2:
        if( $day > 29 ) return FALSE;
        if( $day > 28 && $year % 4 > 0 ) return FALSE;
        break;

      default:
        break;
    }

    $year = $this->add_century_if_missing( $year );

    if(( $year < 1950 && ! $allow_pre_1950 ) || ( $year > 2050 && ! $allow_post_2050 )) return FALSE;

    if( strlen( $parm_value ) > strlen( 'dd/mm/yyyy' )) return FALSE;

    return TRUE;
  }
  #-----------------------------------------------------

  function is_timestamp_query( $parm_value, $allow_blank = TRUE, $allow_pre_1950 = TRUE, $allow_post_2050 = TRUE ) {

    if( strtolower( $parm_value ) == 'today' ) return TRUE;

    if( $allow_blank && $parm_value == '' ) return TRUE;

    $date_length = strlen( 'dd/mm/yyyy' );

    if( strlen( $parm_value ) <= $date_length ) {
      $date_part = $parm_value;
      $time_part = '';
    }
    else {
      $space_pos = strpos( $parm_value, ' ' );
      $date_part = substr( $parm_value, 0, $space_pos );
      $time_part = substr( $parm_value, $space_pos );
      $time_part = ltrim( $time_part );
    }

    if( ! $this->is_dd_mm_yyyy( $date_part, $allow_blank, $allow_pre_1950, $allow_post_2050 ))
      return FALSE;

    if( $time_part ) {
      $hour = '';
      $minute = '';
      $seconds = '';

      $colon_pos1 = strpos( $time_part, ':' );
      $colon_pos2 = strpos( $time_part, ':', $colon_pos1+1 );

      if( $colon_pos1 ) 
        $hour = substr( $time_part, 0, $colon_pos1 );
      else
        $hour = substr( $time_part, 0 );

      if( $colon_pos2 ) {
        $minute = substr( $time_part, $colon_pos1+1, $colon_pos2 - $colon_pos1 - 1 );
        $second = substr( $time_part, $colon_pos2+1 );
      }
      else 
        $minute = substr( $time_part, $colon_pos1+1 );

      if( $hour == '' ) return FALSE;
      if(  $minute == '' ) return FALSE;

      if( ! $this->is_integer( $hour )) return FALSE;
      if( ! $this->is_integer( $minute )) return FALSE;
      if( $second > '' && ! $this->is_integer( $second )) return FALSE;

      if( $hour > 23 || $minute > 59 || $second > 59 ) return FALSE;
    }

    return TRUE;
  }
  #-----------------------------------------------------

  function add_century_if_missing( $year = NULL ) {

    if( $year < 0 || strlen( $year ) == 0 ) return NULL;

    if( $year < 70 ) $year = $year + 2000;  # this seems to match the way Postgres handles dates

    if( $year < 100 ) $year = $year + 1900;

    return $year;
  }
  #-----------------------------------------------------

  function dd_mm_yy_to_yyyy( $parm_value, $allow_blank = TRUE, $allow_pre_1950 = TRUE, $allow_post_2050 = TRUE ) {

    if( $allow_blank && $parm_value == '' ) return $parm_value;

    if( trim( strtolower( $parm_value )) == 'today' ) return $this->get_date_now_dd_mm_yyyy();

    $separators = '/.-';

    $day = strtok( $parm_value,  $separators );
    $month = strtok( $separators );
    $year = strtok( $separators );

    $year = $this->add_century_if_missing( $year );

    if( strlen( $day   ) == 1 ) $day   = '0' . $day;
    if( strlen( $month ) == 1 ) $month = '0' . $month;

    $new_date = $day . '/' . $month . '/' . $year;

    if( ! $this->is_dd_mm_yyyy( $new_date, $allow_blank, $allow_pre_1950, $allow_post_2050 )) return NULL;

    return $new_date;
  }
  #-----------------------------------------------------

  function yyyy_to_dd_mm_yyyy( $parm_name, $parm_value, $write_to_post = TRUE ) {

    if( strlen( $parm_value ) == strlen( 'yyyy' )) {
      if( $this->is_integer( $parm_value )) {  # convert 2008 to 01/01/2008 or 31/12/2008 as appropriate
        $last_char_of_name = substr( $parm_name, -1 );
        if( $last_char_of_name  == '2' ) # end of range
          $parm_value = '31/12/' . $parm_value;
        else
          $parm_value = '01/01/' . $parm_value;

        if( $write_to_post ) $this->write_post_parm( $parm_name, $parm_value );
      }
    }

    return $parm_value;
  }
  #-----------------------------------------------------

  function is_postgres_timestamp( $parm_value, $allow_blank = TRUE ) {

    if( $allow_blank && $parm_value == '' ) return TRUE;

    $first_char = substr( $parm_value, 0, 1 );
    if( $first_char != '2' && $first_char != '1' ) return FALSE;  # Timestamp should start 2001, 1999 etc.

    $year = strtok( $parm_value,  '-' );
    $month = strtok( '-' );
    $day = strtok( ' ' );
    $hour = strtok( ':' );
    $minute = strtok( ':' );
    $second = strtok( '.' );
    $millisecond = strtok( '.' );

    if( ! $this->is_integer( $year ))  return FALSE;
    if( ! $this->is_integer( $month )) return FALSE;
    if( ! $this->is_integer( $day ))   return FALSE;
    if( ! $this->is_integer( $hour ))   return FALSE;
    if( ! $this->is_integer( $minute ))   return FALSE;
    if( ! $this->is_integer( $second ))   return FALSE;
    if( ! $this->is_integer( $millisecond ))   return FALSE;

    if( $day < 1     || $day > 31 )    return FALSE;
    if( $month < 1   || $month > 12 )  return FALSE;
    if( $hour < 0    || $hour > 23 )   return FALSE;
    if( $minute < 0  || $hour > 59 )   return FALSE;
    if( $second < 0  || $second > 59 ) return FALSE;


    return TRUE;
  }
  #-----------------------------------------------------

  function is_on_off_switch( $parm_value ) {

    if( $parm_value == 1  ||  $parm_value == 0 ) return TRUE;
    return FALSE;
  }
  #-----------------------------------------------------

  function is_email_address( $parm_value ) {

    $parm_value = str_replace( '.', '', $parm_value );
    $parm_value = str_replace( '@', '', $parm_value );
    $parm_value = str_replace( '-', '', $parm_value );

    # Allow multiple email addresses for one user, separated by either commas or semi-colons
    $parm_value = str_replace( ',', '', $parm_value );
    $parm_value = str_replace( ';', '', $parm_value );
    $parm_value = str_replace( ' ', '', $parm_value );

    return $this->is_alphanumeric_or_blank( $parm_value, 
                                            $allow_underscores = TRUE, 
                                            $allow_all_whitespace = FALSE );
  }
  #-----------------------------------------------------

  function parm_found_in_post( $parm_name ) {

    if( key_exists( $parm_name, $_POST )) 
      return TRUE;
    else
      return FALSE;
  }
  #-----------------------------------------------------

  function parm_found_in_get( $parm_name ) {

    if( key_exists( $parm_name, $_GET )) 
      return TRUE;
    else
      return FALSE;
  }
  #-----------------------------------------------------

  function parm_found_in_session( $parm_name ) {

    if( key_exists( $parm_name, $_SESSION )) 
      return TRUE;
    else
      return FALSE;
  }
  #-----------------------------------------------------

  function string_contains_substring( $big_string, $little_string ) {

    if( strpos( $big_string, $little_string ) > 0 )
      return TRUE;
    elseif( substr( $big_string, 0, strlen( $little_string )) == $little_string )
      return TRUE;
    else
      return FALSE;
  }
  #-----------------------------------------------------

  function string_starts_with( $big_string, $little_string, $ignore_case = FALSE ) {

    $big_length    = strlen( $big_string );
    $little_length = strlen( $little_string );

    if( $little_length > $big_length ) return FALSE;
    if( $little_length == 0 ) return FALSE;

    if( $ignore_case ) {
      $big_string = strtolower( $big_string ); 
      $little_string = strtolower( $little_string ); 
    }

    if( substr( $big_string, 0, $little_length ) == $little_string )
      return TRUE;
    else
      return FALSE;
  }
  #-----------------------------------------------------

  function string_ends_with( $big_string, $little_string, $ignore_case = FALSE ) {

    $big_length    = strlen( $big_string );
    $little_length = strlen( $little_string );

    if( $little_length > $big_length ) return FALSE;
    if( $little_length == 0 ) return FALSE;

    if( $ignore_case ) {
      $big_string = strtolower( $big_string ); 
      $little_string = strtolower( $little_string ); 
    }

    if( substr( $big_string, 0 - $little_length ) == $little_string )
      return TRUE;
    else
      return FALSE;
  }

  #-----------------------------------------------------

  function safe_output( $the_value ) {
    return html::call_htmlentities( $the_value );
  }
  #-----------------------------------------------------

  function echo_safely( $the_value ) {
    echo html::call_htmlentities( $the_value );
  }
  #-----------------------------------------------------

  function echo_safely_with_linebreaks( $the_value ) {

    $blank_line_count = 0;

    if( $this->string_contains_substring( $the_value, NEWLINE )) {
      $lines = explode( NEWLINE, $the_value );
      foreach( $lines as $one_line ) {
        if( trim( $one_line ) > '' ) {
          $blank_line_count = 0;
          $this->echo_safely( $one_line );
          echo LINEBREAK;
        }
        else {
         $blank_line_count++;
         if( $blank_line_count < 2 ) echo LINEBREAK;
        }
      }
    }
    else
      $this->echo_safely( $the_value );
  }
  #-----------------------------------------------------

  function re_enable_tag( $the_value, $tag = NULL ) {

    if( ! $tag ) return $the_value;
    $disabled_tag = html::call_htmlentities( $tag );
    $the_value = str_replace( $disabled_tag, $tag, $the_value );
    return $the_value;
  }
  #-----------------------------------------------------

  function die_on_error( $err_msg = NULL ) {

    html::new_paragraph();

    $this->echo_safely( $err_msg );

    html::new_paragraph();

    $focus_form  = NULL;
    $focus_field = NULL;

    if( $this->parm_found_in_post( 'focus_form' )) 
      $focus_form = $this->read_post_parm( 'focus_form' );
    if( $this->parm_found_in_post( 'focus_field' )) 
      $focus_field = $this->read_post_parm( 'focus_field' );

    if( $focus_form && $focus_field ) {  # avoid Javascript errors by providing a field for focus
      html::form_start( $class_name=NULL, $method_name=NULL, $form_name=$focus_form );
      html::input_field( $focus_field, NULL, NULL, NULL, 1 );
      html::italic_start();
      echo 'Hit Return to go back to the main menu.';
      html::italic_end();
      html::form_end();
    }

    die();
  }
  #-----------------------------------------------------

  function print_percentage( $divided, $divisor ) {

    if( ! $divisor )
      printf( "%5.1f%%", 0 );
    else
      printf( "%5.1f%%", ($divided / $divisor) * 100 );
  }
  #-----------------------------------------------------

  function add_default_value( $field_id, $data_to_add, $clear_previous = FALSE, $separator = ' ' ) {

    echo NEWLINE;

    $separator = trim( $separator );
    if( $separator )
      $separator = ' ' . $separator . ' ';  # e.g. ' - '
    else
      $separator = ' ';

    $this->echo_safely( $data_to_add );
    echo ': ';
    echo '<input type="button"  value="Add"  onclick="';

    if( $clear_previous )
      echo "$field_id.value = ";
    else
      echo "$field_id.value = $field_id.value + '$separator' + ";

    echo "'" . $this->escape( $data_to_add ) . "'" . '">';

    echo NEWLINE;
  }
  #-----------------------------------------------------

  function yes_no_dropdown_int( $fieldname, $label, $selected_value, $in_table = FALSE,
                                $include_blank_row = FALSE ) {

    html::dropdown_start( $fieldname, $label, $in_table );
    if( $include_blank_row ) html::dropdown_option( 'null', '', $selected_value );
    html::dropdown_option( 1, 'Yes', $selected_value );
    html::dropdown_option( 0, 'No', $selected_value );
    html::dropdown_end();
  }
  #-----------------------------------------------------

  function yes_no_dropdown_char( $fieldname, $label, $selected_value = NULL, $in_table = FALSE, 
                                 $include_blank_row = FALSE ) {

    html::dropdown_start( $fieldname, $label, $in_table );
    if( $include_blank_row ) html::dropdown_option( '', '', $selected_value );
    html::dropdown_option( 'Y', 'Yes', $selected_value );
    html::dropdown_option( 'N', 'No', $selected_value );
    html::dropdown_end();
  }
  #-----------------------------------------------------

  function display_errmsg( $parm_name, $errmsg = NULL ) {

    $this->failed_validation = TRUE;
    html::div_start( 'class="errmsg"' );
    echo 'Error: field "' . ucfirst( str_replace( '_', ' ', $parm_name )) . '": ' . $errmsg;
    html::div_end();
    html::new_paragraph();
  }
  #-----------------------------------------------------

  function list_user_roles() {

    $this->user_roles = $this->read_session_parm( 'user_roles' );
    if( $this->user_roles )
      $user_role_array = explode( ',', $this->user_roles );
    else
      $user_role_array = array();

    foreach( $user_role_array as $keyval => $role ) {
      $role = str_replace( "'", '', $role );
      $user_role_array[ $keyval ] = trim( $role );
    }

    return $user_role_array;
  }
  #-----------------------------------------------------

  function list_user_role_ids() {

    $this->user_role_ids = $this->read_session_parm( 'user_role_ids' );
    if( $this->user_role_ids )
      $user_role_array = explode( ',', $this->user_role_ids );
    else
      $user_role_array = array();

    foreach( $user_role_array as $keyval => $role ) {
      $role = trim( $role );
      $user_role_array[ $keyval ] = trim( $role );
    }

    return $user_role_array;
  }
  #-----------------------------------------------------

  function get_supervisor() { # N.B. We must create a supervisor username in a predictable pattern for each DB

    return $this->get_system_prefix() . SUPERVISOR_ROLE_CODE;

  }
  #-----------------------------------------------------

  function csv_field( $field_value = NULL ) {

    $field_value = str_replace( '"', '""', $field_value );

    $field_value = trim( $field_value );

    $needs_wrapping_in_quotes = FALSE;

    if( $this->string_contains_substring( $field_value, ',' )) 
      $needs_wrapping_in_quotes = TRUE;

    if( ! $needs_wrapping_in_quotes ) {
      if( $this->string_contains_substring( $field_value, '"' )) 
        $needs_wrapping_in_quotes = TRUE;
    }

    if( ! $needs_wrapping_in_quotes ) {
      if( $this->string_contains_substring( $field_value, NEWLINE )) 
        $needs_wrapping_in_quotes = TRUE;
    }

    if( $needs_wrapping_in_quotes ) $field_value = '"' . $field_value . '"';

    return trim( $field_value  );
  }
  #-----------------------------------------------------

  function send_plain_text_attachment( $file_content, 
                                       $email_to = NULL, 
                                       $msg_body = NULL, 
                                       $msg_subject = NULL, 
                                       $file_name = 'QueryResults.csv',
                                       $confirmation_msg = TRUE ) {

    $email_from = $this->read_session_parm( 'user_email' );

    if( ! $email_to ) $email_to = $email_from;
    if( ! $email_to ) $this->die_on_error( 'Cannot send email: no recipient address specified.');

    $email_to = str_replace( ';', ',', $email_to );

    if( ! $email_from ) $email_from_descrip = CFG_SYSTEM_TITLE . ' ' . NO_REPLY_EMAIL_SENDER;

    if( ! $msg_body ) 
      $msg_body = ' - Results are attached from your query on the ' . CFG_SYSTEM_TITLE . '.    '
                . CARRIAGE_RETURN . NEWLINE
                . ' - Menu option was: ' . $this->menu_item_name . '.    '
                . CARRIAGE_RETURN . NEWLINE
                . ' - Data is in attached CSV file ' . $file_name . '.'; 

    $mime_boundary = '<<<---===XXX[' . md5(time()) . ']';
    $headers = '';
    if( $email_from ) $headers .= "From: $this->person_name <"  . $email_from . '>' . NEWLINE; 
    elseif( $email_from_descrip ) $headers .= "From: $email_from_descrip" . NEWLINE; 

    $headers .= 'MIME-Version: 1.0' . NEWLINE;
    $headers .= 'Content-Type: multipart/mixed;' . NEWLINE;
    $headers .= ' boundary="' . $mime_boundary . '"';

    $message = '';
    $message .= 'This is a multi-part message in MIME format.' . NEWLINE;
    $message .= NEWLINE;
    $message .= '--' . $mime_boundary . NEWLINE;

    $message .= 'Content-Type: text/plain; charset="' . strtolower( $this->get_character_encoding()) . '"' . NEWLINE;
    $message .= 'Content-Transfer-Encoding: 7bit' . NEWLINE;
    $message .= NEWLINE;
    $message .= $this->app_get_unicode_signature() . $msg_body . NEWLINE;
    $message .= '--' . $mime_boundary . NEWLINE;

    $message .= 'Content-Type: application/octet-stream;' . NEWLINE;
    $message .= ' name="' . $file_name . '"' . NEWLINE;
    $message .= 'Content-Transfer-Encoding: base64' . NEWLINE;
    $message .= 'Content-Disposition: attachment;' . NEWLINE;
    $message .= ' filename="' . $file_name . '"' . NEWLINE;
    $message .= NEWLINE;
    $message .= chunk_split( base64_encode( $this->app_get_unicode_signature() . $file_content ));
    $message .= NEWLINE;
    $message .= '--' . $mime_boundary . '--' . NEWLINE;

    if( ! $msg_subject ) $msg_subject = CFG_SYSTEM_TITLE . ' query results';

    $success = mail( $email_to, $msg_subject, $message, $headers );

    if( $confirmation_msg ) {

      if( $success ) {
        html::h3_start();
        echo 'Query results were emailed to you as a CSV attachment.';
        html::h3_end();
        html::new_paragraph();
        echo 'The file was sent to the following address:';
        html::new_paragraph();
        echo $email_to ;

        html::new_paragraph();
        html::italic_start();
        echo '(You may now like to close the current tab/window and continue working in your original tab/window.)';
        html::italic_end();

        html::new_paragraph();
        html::button( 'close_button', 'Close', $tabindex=1, 'onclick="self.close()"' );
      }

      else {
        echo 'Sorry, query results could not be sent to you (email error).';
        html::new_paragraph();
        echo 'Query results are instead being displayed below, so you can copy and paste them into another file...';
        html::new_paragraph();
        $file_content = str_replace( CARRIAGE_RETURN, LINEBREAK, $file_content );
        echo $file_content;
      }

      html::new_paragraph();
    }


    return $success;
  }
  #-----------------------------------------------------

  function write_js_open_search_window() {

    $script  = 'function searchWindow( file, window_name ) {'                                 . NEWLINE;
    $script .= '  searchPopup=open( file, window_name,';
    $script .= "  'resizable=yes, width=" . POPUP_WINDOW_WIDTH . ',';
    $script .= ' height=' . POPUP_WINDOW_HEIGHT . ", scrollbars=1' );"                        . NEWLINE;
    $script .= "  if ( searchPopup.opener == null ) searchPopup.opener = self;"               . NEWLINE;
    $script .= "  searchPopup.focus();"                                                       . NEWLINE;
    $script .= "}"                                                                            . NEWLINE;

    html::write_javascript_function( $script );

  }
  #-----------------------------------------------------

  function write_js_check_form_valid() {

    $script  = 'function js_check_form_valid( the_form ) {'                       . NEWLINE;
    $script .= '  if( the_form.requires_validation.value == 0 ) {'                . NEWLINE;
    $script .= '    return true;'                                                 . NEWLINE;
    $script .= '  }'                                                              . NEWLINE;
    $script .= '  if( the_form.validation_err.value > 0 ) {'                      . NEWLINE;
    $script .= "    alert('Cannot save changes due to invalid data');"            . NEWLINE;
    $script .= '    return false;'                                                . NEWLINE;
    $script .= '  }'                                                              . NEWLINE;
    $script .= '  if( the_form.cancel_submission.value > 0 ) {'                   . NEWLINE;
    $script .= '    return false;'                                                . NEWLINE;
    $script .= '  }'                                                              . NEWLINE;
    $script .= '  return true;'                                                   . NEWLINE;
    $script .= '}';

    html::write_javascript_function( $script );
  }
  #-----------------------------------------------------

  function write_js_drop_form_validation() {

    $script  = 'function js_drop_form_validation( the_button ) {'                   . NEWLINE;
    $script .= '  var the_form = the_button.form;'                                  . NEWLINE;
    $script .= '  the_form.requires_validation.value = 0;'                          . NEWLINE;
    $script .= '  the_form.validation_err.value = 0;'                               . NEWLINE;
    $script .= '  if( the_form.focus_field )'                                       . NEWLINE;
    $script .= "    the_form.focus_field.value = '';"                               . NEWLINE;
    $script .= '}';

    html::write_javascript_function( $script );
  }
  #-----------------------------------------------------

  function write_js_check_value_is_numeric() {

    $script  = 'function js_check_value_is_numeric( the_element ) {'                       . NEWLINE;
    $script .= '  var one_char;'                                                           . NEWLINE;
    $script .= '  var goodval=true;'                                                       . NEWLINE;
    $script .= '  var decimalpoints=0;'                                                    . NEWLINE;
    $script .= '  var the_form;'                                                           . NEWLINE;
    $script .= '  fieldval = the_element.value;'                                           . NEWLINE;
    $script .= '  the_form = the_element.form;'                                            . NEWLINE;
    $script .= '  the_form.requires_validation.value = 1;'                                 . NEWLINE;
    $script .= '  the_form.validation_err.value = 0;'                                      . NEWLINE;
    $script .= '  for( i = 0; i < fieldval.length; i++ ) {'                                . NEWLINE;
    $script .= '    one_char = fieldval.substr( i, 1);'                                    . NEWLINE;
    $script .= "    if( one_char < '0' || one_char > '9') {"                               . NEWLINE;
    $script .= "      if( i == 0 && one_char == '-' ) {"                                   . NEWLINE;
    $script .= '        continue;'                                                         . NEWLINE;
    $script .= '      }'                                                                   . NEWLINE;
    $script .= "      else if( one_char == '.' && decimalpoints == 0 ) { "                 . NEWLINE;
    $script .= '        decimalpoints++;'                                                  . NEWLINE;
    $script .= '        continue;'                                                         . NEWLINE;
    $script .= '      }'                                                                   . NEWLINE;
    $script .= "      else {"                                                              . NEWLINE;
    $script .= '        goodval=false;'                                                    . NEWLINE;
    $script .= '        break;'                                                            . NEWLINE;
    $script .= '      }'                                                                   . NEWLINE;
    $script .= '    }'                                                                     . NEWLINE;
    $script .= '  }'                                                                       . NEWLINE;
    $script .= '  if( ! goodval ) {'                                                       . NEWLINE;
    $script .= '    the_form.validation_err.value = 1;'                                    . NEWLINE;
    $script .= "    alert('Invalid entry in numeric field: only numbers may be entered');" . NEWLINE;
    $script .= '  }'                                                                       . NEWLINE;
    $script .= '  return goodval;'                                                         . NEWLINE;
    $script .= '}';

    html::write_javascript_function( $script );
  }
  #-----------------------------------------------------

  function write_js_prompt_to_confirm_submission() {   # To make this work, you need to turn the form's
                                                       # onsubmit property on. See html::form_start().

    if( ! is_array( $fieldlist )) $fieldlist = array();

    $script = 'function js_prompt_to_confirm_submission() { '              . NEWLINE;

    $script .= "  document.$this->form_name.cancel_submission.value=0;" . NEWLINE;
    $script .= '  var proceedWithSubmit = true;'                        . NEWLINE;
    $script .= '  var unsavedChanges = false;'                          . NEWLINE;
    $script .= '  var oneField;'                                        . NEWLINE;

    $script .= "  var fields = document.getElementsByTagName('input');" . NEWLINE;
    $script .= '  for( i=0; i < fields.length; i++ ) {'                 . NEWLINE;
    $script .= '    oneField = fields[i];'                              . NEWLINE;

    $script .= '    if( oneField.type == "text" ) {'                    . NEWLINE;
    $script .= '      if( oneField.value.replace("\n","").replace("\r","")';
    $script .= '      != oneField.defaultValue.replace("\n","").replace("\r","") ) {'  . NEWLINE;
    $script .= '        unsavedChanges = true;'                         . NEWLINE;
    $script .= '        break;'                                         . NEWLINE;
    $script .= '      }'                                                . NEWLINE;
    $script .= '    }'                                                  . NEWLINE;

    $script .= '    else if( oneField.type == "radio" || oneField.type == "checkbox" ) {' . NEWLINE;
    $script .= '      if( oneField.checked != oneField.defaultChecked ) {'                . NEWLINE;
    $script .= '        unsavedChanges = true;'                                           . NEWLINE;
    $script .= '        break;'                                                           . NEWLINE;
    $script .= '      }'                                                                  . NEWLINE;
    $script .= '    }'                                                                    . NEWLINE;
    $script .= '  }'                                                                      . NEWLINE;

    $script .= '  if( unsavedChanges == false ) {'                           . NEWLINE;   # Check textareas
    $script .= "    fields = document.getElementsByTagName('textarea');"     . NEWLINE;
    $script .= '    for( i=0; i < fields.length; i++ ) {'                    . NEWLINE;
    $script .= '      oneField = fields[i];'                                 . NEWLINE;
    $script .= '      if( oneField.value != oneField.defaultValue ) {'       . NEWLINE;
    $script .= '        unsavedChanges = true;'                              . NEWLINE;
    $script .= '        break;'                                              . NEWLINE;
    $script .= '      }'                                                     . NEWLINE;
    $script .= '    }'                                                       . NEWLINE;
    $script .= '  }'                                                         . NEWLINE;

    $script .= '  if( unsavedChanges == false ) {'                      . NEWLINE;   # Check dropdown lists
    $script .= "    fields = document.getElementsByTagName('select');"  . NEWLINE;
    $script .= '    for( i=0; i < fields.length; i++ ) {'               . NEWLINE;
    $script .= '      oneField = fields[i];'                            . NEWLINE;
    $script .= '      selectedOption = oneField.selectedIndex;'         . NEWLINE;
    $script .= '      oneOption = oneField.options[ selectedOption ];'  . NEWLINE;
    $script .= '      if( ! oneOption.defaultSelected ) {'              . NEWLINE;
    $script .= '        unsavedChanges = true;'                         . NEWLINE;
    $script .= '        break;'                                         . NEWLINE;
    $script .= '      }'                                                . NEWLINE;
    $script .= '    }'                                                  . NEWLINE;
    $script .= '  }'                                                    . NEWLINE;

    $script .= '  if( unsavedChanges == true ) {'                             . NEWLINE;
    $script .= "    proceedWithSubmit = confirm( 'You have made changes that will be lost if you leave the form"
                    . " without saving. Abandon your changes?' );"            . NEWLINE;
    $script .= '    if( ! proceedWithSubmit ) {'                              . NEWLINE;
    $script .= "      document.$this->form_name.requires_validation.value=1;" . NEWLINE;
    $script .= "      document.$this->form_name.cancel_submission.value=1;"   . NEWLINE;
    $script .= '    }'                                                        . NEWLINE;
    $script .= '  }'                                                          . NEWLINE;
    $script .= '  return proceedWithSubmit;'                                  . NEWLINE;
    $script .= '}'                                                            . NEWLINE;

    html::write_javascript_function( $script );
  }
  #-----------------------------------------------------

  function write_js_enable_form_submission() {   # works with write_js_prompt_to_confirm_submission

    $script = 'function js_enable_form_submission() { '                    . NEWLINE;
    $script .= "  document.$this->form_name.cancel_submission.value=0;"    . NEWLINE;
    $script .= '}'                                                         . NEWLINE;

    html::write_javascript_function( $script );
  }
  #-----------------------------------------------------

  function write_js_focus_function( $field_id ) {

    if( ! $field_id ) return NULL;
    if( ! $this->form_name ) return NULL;

    # Write a function to set the focus back on a field after Javascript validation has failed
    $function_name  = $field_id . '_focus()';

    $function_body  = NEWLINE;
    $function_body .= 'function ' . $function_name . ' {'      . NEWLINE;
    $function_body .= "  document.$this->form_name.$field_id.focus();"        . NEWLINE;
    $function_body .= '}' . NEWLINE;
    html::write_javascript_function( $function_body );

    return $function_name;
  }
  #-----------------------------------------------------
  #############################################################################################################
  # N.B. This script MUST be run in conjunction with setting "onsubmit validation" parameter to TRUE
  # in call to html::form_start(). Without this, no "onsubmit" validation will take place.
  #
  # You also MUST set value of $this->form_name. You can do so by using the return value of html::form_start().
  #############################################################################################################

  function get_js_to_check_value_is_numeric( $field_id ) {

    if( ! $field_id ) return NULL;
    if( ! $this->form_name ) return NULL;

    # Write a function to set the focus back on the numeric field if non-numeric value entered.
    $function_name = $this->write_js_focus_function( $field_id );

    # If validation fails, put focus back on numeric field, avoiding timing problem caused by normal focus() method.
    # The "set timeout" runs the specified function after N milliseconds, in this case 100.
    # We are using this because otherwise the focus goes extremely briefly (i.e. unnoticeably briefly)
    # onto the required field, and then back onto the next field in the form.

    $script = 'onchange="if( ! js_check_value_is_numeric( this )) {' . NEWLINE
            . "  setTimeout( '" . $function_name . "', 100 );"       . NEWLINE
            . '}"';

    return $script;
  }
  #-----------------------------------------------------

  function user_is_supervisor() {

    $role_ids = $this->list_user_role_ids(); 
    if( is_array( $role_ids )) {
      foreach( $role_ids as $role_id ) {
        if( $role_id == SUPERVISOR_ROLE_ID ) return TRUE;
      }
    }

    return FALSE;
  }
  #-----------------------------------------------------

  function get_system_prefix() {
    return CFG_PREFIX;
  }
  #-----------------------------------------------------

  function debug_boolean( $boolvar, $include_linebreak = TRUE ) {

    if( $boolvar )
      $bool_display = ' TRUE ';
    else
      $bool_display = ' FALSE ';

    if( $include_linebreak ) $bool_display .= LINEBREAK;
    return $bool_display;
  }
  #-----------------------------------------------------

  function get_month_number_from_name( $the_string, $pad=TRUE ) {  # will return NULL if string is not a month name

    $the_string = trim( strtolower( $the_string ));
    $the_string = str_replace( '.', '', $the_string );  # convert Mar. to mar etc.

    $month_number = NULL;

    switch( $the_string ) {

      case 'january':
      case 'jan':
        $month_number = 1;
        break;

      case 'february':
      case 'feb':
        $month_number = 2;
        break;

      case 'march':
      case 'mar':
        $month_number = 3;
        break;

      case 'april':
      case 'apr':
        $month_number = 4;
        break;

      case 'may':
        $month_number = 5;
        break;

      case 'june':
      case 'jun':
        $month_number = 6;
        break;

      case 'july':
      case 'jul':
        $month_number = 7;
        break;

      case 'august':
      case 'aug':
        $month_number = 8;
        break;

      case 'september':
      case 'sept':
      case 'sep':
        $month_number = 9;
        break;

      case 'october':
      case 'oct':
        $month_number = 10;
        break;

      case 'november':
      case 'nov':
        $month_number = 11;
        break;

      case 'december':
      case 'dec':
        $month_number = 12;
        break;

      default:
        break;
    }

    if( $month_number && $month_number <= 9 && $pad ) $month_number = '0' . $month_number;
    return $month_number;
  }
  #-----------------------------------------------------

  function is_month_name( $the_string ) {

    if( $this->get_month_number_from_name( $the_string ))
      return TRUE;
    else
      return FALSE;
  }
  #-----------------------------------------------------

  function get_month_name_from_number( $month_number ) {  # will return NULL if not in range 1-12

    switch( $month_number ) {

      case 1: return 'Jan';
      case 2: return 'Feb';
      case 3: return 'Mar';
      case 4: return 'Apr';
      case 5: return 'May';
      case 6: return 'Jun';
      case 7: return 'Jul';
      case 8: return 'Aug';
      case 9: return 'Sep';
      case 10: return 'Oct';
      case 11: return 'Nov';
      case 12: return 'Dec';
    }

    return NULL;
  }
  #-----------------------------------------------------

  function close_self_button() {
    html::button( 'close_self_button', 'Close', $tabindex=1, 'onclick="self.close()"' );
  }
  #-----------------------------------------------------

  function read_opening_class_and_method() {

    $this->opening_class = $this->read_post_parm( 'opening_class' );
    $this->opening_method = $this->read_post_parm( 'opening_method' );
  }
  #-----------------------------------------------------

  function copy_opening_class_and_method() {

    html::hidden_field( 'opening_class', $this->opening_class );
    html::hidden_field( 'opening_method', $this->opening_method );
  }
  #-----------------------------------------------------

  function msg_and_close_button( $msg = NULL ) {

    $this->read_opening_class_and_method();
    if(( ! $this->opening_class ) || ( ! $this->opening_method )) return;  # don't try to close the only tab

    html::new_paragraph();
    html::div_start( 'class="highlight2"' );
    echo $msg;
    if( $msg ) echo ' ';
    $this->close_self_button();
    html::div_end( 'highlight2' );
    html::new_paragraph();
  }
  #-----------------------------------------------------
  #*****************************************************
  # Methods for returning values from popup windows
  #*****************************************************

  function app_popup_searchform_caller( $calling_form, $calling_field, $method_to_call = NULL,
                                        $select_desc = 'Select existing data', $view_desc = 'View list' ) {

    if( ! $calling_form || ! $calling_field ) return;
    $this->calling_form  = $calling_form;
    $this->calling_field = $calling_field;

    if( ! $method_to_call ) $method_to_call = $this->app_popup_get_search_results_method();

    $function_name = $this->write_popup_window_caller( $calling_form, $calling_field, $method_to_call );

    $script = ' onClick="' . $function_name . '()" ';

    if( $this->publicly_available_page || $this->app_popup_from_query_field() ) {
      echo $view_desc;
      $button_label = $this->app_popup_get_view_button_label();
    }
    else {
      echo $select_desc;
      $button_label = $this->app_popup_get_select_button_label();
    }

    html::button( $calling_field . '_list_button', $button_label, $tabindex=1, $script );
  }
  #-----------------------------------------------------

  function app_popup_addform_caller( $calling_form, $calling_field, $method_to_call = NULL, 
                                 $option_desc = 'Create new data' ) {

    if( ! $calling_form || ! $calling_field ) return;
    $this->calling_form  = $calling_form;
    $this->calling_field = $calling_field;

    if( ! $method_to_call ) $method_to_call = $this->app_popup_get_add_method();

    # Function name mustn't clash with 'search' function name, so use a different prefix.
    $function_name = $this->app_popup_get_default_function_name( $calling_field, $prefix = 'aeolusCreate' );

    $function_name = $this->write_popup_window_caller( $calling_form, $calling_field, $method_to_call,
                                                       NULL, NULL, $function_name );

    $script = ' onClick="' . $function_name . '()" ';

    echo $option_desc;

    html::button( $calling_field . '_add_button', $this->app_popup_get_create_button_label(), $tabindex=1, $script );
  }
  #-----------------------------------------------------

  function app_popup_get_view_button_label() {
    return 'View';
  }
  #-----------------------------------------------------

  function app_popup_get_select_button_label() {
    return 'Select';
  }
  #-----------------------------------------------------

  function app_popup_get_create_button_label() {
    return 'Create';
  }
  #-----------------------------------------------------

  function write_popup_window_caller( $calling_form, $calling_field, 
                                      $menu_method_name=NULL,                   # only applicable to logged-in users
                                      $window_height=NULL, $window_width=NULL,  # only applicable to CMS
                                      $function_name = NULL ) {

    if( ! $calling_form || ! $calling_field ) return NULL;

    $class_name = $this->app_get_class( $this );

    if( ! $function_name ) {
      $function_name = $this->app_popup_get_default_function_name( $calling_field );
    }
    $window_name = strtolower( $function_name ) . 'window';

    $script  = 'function ' . $function_name . '() {'                                               . NEWLINE;

    if( $this->publicly_available_page ) {  # Not logged in, running via the CMS.
                                            # (See Poetic Miscellanies for example of use of this.)

      if( ! $window_height ) $window_height = POPUP_WINDOW_HEIGHT;
      if( ! $window_width ) $window_width = CMS_POPUP_WINDOW_WIDTH;

      $script .= '  formaction=document.' . $calling_form . '.action;'                             . NEWLINE;

      # Avoid getting a cached page which does not show latest data 
      $script .= '  if( formaction.indexOf( "sq_content_src" ) > 0 && formaction.indexOf( "/_nocache" ) <= 0 ) {' 
                                                                                                   . NEWLINE;
      $script .= '    formaction = formaction.replace( "?sq_content_src", "/_nocache?sq_content_src"); ' 
                                                                                                   . NEWLINE;
      $script .= '  }'                                                                             . NEWLINE;

      $script .= '  formaction=formaction + "&class_name=' . $class_name;
      $script .= "&calling_form=$calling_form&calling_field=$calling_field";
      $script .= '";'                                                                              . NEWLINE;

      $script .= "  searchPopup=open( formaction, '$window_name',";
      $script .= "  'resizable=yes, width=$window_width, height=$window_height, scrollbars=1' );"  . NEWLINE;
      $script .= "  if ( searchPopup.opener == null ) searchPopup.opener = self;"                  . NEWLINE;
      $script .= "  searchPopup.focus();"                                                          . NEWLINE;
    }

    else {  # Logged-in user, possibly using the popup window to select values for data entry

      $menu_item = new Menu( $this->db_connection, $item_id=NULL, $class_name, $menu_method_name );
      $menu_item_id = $menu_item->get_menu_item_id();

      $script .= 'searchWindow('  # Javascript function written earlier in page head
              .  "'" . $_SERVER['PHP_SELF'] 
              .  '?' . SESSION_TOKEN_FIELD . '=' . rawurlencode( $_SESSION['session_token'] )
              .  '&menu_item_id=' . $menu_item_id
              .  '&calling_form=' . $calling_form
              .  '&calling_field=' . $calling_field
              .  "', '$window_name')" . NEWLINE;
    }
    $script .= "}"                                                                                 . NEWLINE;

    html::write_javascript_function( $script );

    # $call = ' onClick="' . $function_name . '()" ';

    return $function_name;
  }
  #-----------------------------------------------------

  function app_popup_get_default_function_name( $calling_field, $prefix = 'aeolus' ) {

    $function_name = str_replace( '_', ' ', $calling_field );
    $function_name = ucwords( $function_name );
    $function_name = str_replace( ' ', '', $function_name );
    $function_name = $prefix . $function_name;

    return $function_name;
  }
  #-----------------------------------------------------

  function app_popup_pass_value_back() {

    if( $this->csv_output || $this->printable_output ) return;

    if( ! $this->calling_form || ! $this->calling_field ) {
      $this->calling_form = $this->read_post_parm( 'calling_form' );
      $this->calling_field = $this->read_post_parm( 'calling_field' );
    }

    $escaped_app_popup_result_text = $this->escape( $this->app_popup_set_result_text() );
    $escaped_app_popup_result_text = str_replace( NEWLINE, ' ', $escaped_app_popup_result_text );
    $escaped_app_popup_result_text = str_replace( CARRIAGE_RETURN, ' ', $escaped_app_popup_result_text );
    $escaped_app_popup_result_text = str_replace( '"', "'", $escaped_app_popup_result_text );

    $called_by_query_field = $this->app_popup_from_query_field();

    $this->script = 'onClick="';

    if ( $called_by_query_field ) {
      $this->app_popup_pass_text_back( $escaped_app_popup_result_text );
    }

    else {

      $this->app_popup_pass_id_back( $id_value = $this->app_popup_set_result_id(), 
                                     $decode_field_name = $this->app_popup_get_decode_fieldname(), 
                                     $decode_field_value = $escaped_app_popup_result_text, 
                                     $focus_field = $this->app_popup_get_focus_fieldname() );
    }

    $this->script .= 'self.close();"';

    echo ' ';
    html::button( 'select_button', 'OK', $tabindex = 1, $this->script );
  }
  #-----------------------------------------------------

  function app_popup_pass_text_back( $text_field_value = NULL ) {

    $this->app_popup_set_value_of_field_in_opener( $this->calling_field, $text_field_value );
    $this->app_popup_highlight_field_in_opener( $this->calling_field );
    $this->app_popup_focus_on_field_in_opener(  $this->calling_field );
  }
  #-----------------------------------------------------

  function app_popup_pass_id_back( $id_value = NULL, $decode_field_name = NULL, $decode_field_value = NULL, 
                               $focus_field = NULL ) {

    $this->app_popup_set_value_of_field_in_opener( $this->calling_field, $id_value );

    if( $decode_field_name ) {
      $this->app_popup_set_value_of_field_in_opener( $decode_field_name, $decode_field_value );
      $this->app_popup_highlight_field_in_opener( $decode_field_name );
    }

    if( $focus_field ) 
      $this->app_popup_focus_on_field_in_opener( $focus_field );
  }
  #-----------------------------------------------------

  function app_popup_set_value_of_field_in_opener( $field_name, $field_value ) {  # used by popup windows

    $this->script .= "opener.document.$this->calling_form.$field_name.value = '$field_value';" . NEWLINE;
  }
  #-----------------------------------------------------
  function app_popup_highlight_field_in_opener( $field_name ) {  # used by popup windows

    $this->script .= "opener.document.$this->calling_form.$field_name.className = 'highlight2';" .  NEWLINE;
  }
  #-----------------------------------------------------
  function app_popup_focus_on_field_in_opener( $field_name ) {  # used by popup windows

    $this->script .= "opener.document.$this->calling_form.$field_name.focus();" .  NEWLINE;
  }
  #-----------------------------------------------------

  function app_popup_read_calling_form_and_field() {

    $this->calling_form = $this->read_get_parm( 'calling_form' );
    $this->calling_field = $this->read_get_parm( 'calling_field' );

    if( ! $this->calling_form || ! $this->calling_field ) {
      $this->calling_form = $this->read_post_parm( 'calling_form' );
      $this->calling_field = $this->read_post_parm( 'calling_field' );
    }
  }
  #-----------------------------------------------------

  function app_popup_write_calling_form_and_field() {

    html::hidden_field( 'calling_form', $this->calling_form );
    html::hidden_field( 'calling_field', $this->calling_field );
  }
  #-----------------------------------------------------

  function app_popup_set_result_id() {  # Can be overwritten in child class

    $keycol = $this->keycol;

    if( $keycol ) # Should have been set in DB Set Search Result Parms
      $this->app_popup_result_id = $this->current_row_of_data[ "$keycol" ];  
    else
      $this->app_popup_result_id = NULL;  # Popup result ID should go here  
    return $this->app_popup_result_id;
  }
  #-----------------------------------------------------

  function app_popup_set_result_text() {  # Must be overwritten in child class
                                          # in order to pass text or other non-ID value back from a popup window.

                                          # E.g. $this->app_popup_result_text = $this->primaryname;  

    $this->app_popup_result_text = 'Popup result text should go here';  
    return $this->app_popup_result_text;
  }
  #-----------------------------------------------------

  function app_popup_get_field_for_select_button() {  # Can and often should be overridden in child class.
                                                      # E.g. return 'person_id' to make a 'Select' button appear
                                                      # in the Person ID column of the popup window.
    return APP_POPUP_SELECTFORM_NAME;
  }
  #-----------------------------------------------------

  function app_popup_from_query_field() {  # Must be overwritten in child class

    # Child class must check value of $this->calling_field (plus possibly form) and
    # return TRUE if calling field is part of a search form. Otherwise return FALSE.

    return FALSE;
  }
  #-----------------------------------------------------

  function app_popup_get_decode_fieldname() {  # Must be overwritten in child class

    # Child class must check value of $this->calling_field (plus possibly form) and
    # return name of field in which text decode for ID should be displayed.

    return 'Decode fieldname should go here';
  }
  #-----------------------------------------------------

  function app_popup_get_focus_fieldname() {  # Must be overridden by child class

    # Child class must check value of $this->calling_field (plus possibly form) and
    # return name of field on which to focus after returning ID value into a hidden field.

    return 'Focus fieldname should go here';
  }
  #-----------------------------------------------------

  function app_popup_get_search_table() {  # Must be overwritten in child class

    return 'Search tablename should go here';
  }
  #-----------------------------------------------------

  function app_popup_get_search_method() {  # Could be overridden in child class

    return 'app_popup_search';
  }
  #-----------------------------------------------------

  function app_popup_get_search_results_method() {  # Could be overridden in child class

    return 'app_popup_search_results';
  }
  #-----------------------------------------------------

  function app_popup_get_add_method() {  # Could be overridden in child class

    return 'app_popup_add_record' ;
  }
  #-----------------------------------------------------

  function app_popup_get_order_by_col() {  # Could be overridden in child class

    return '1';
  }
  #-----------------------------------------------------

  function app_popup_title_for_public_page( $title = NULL ) {  # Override in child class if needed

    if( ! $title ) return;

    html::h2_start();
    echo $title;
    html::h2_end();
  }
  #-----------------------------------------------------

  function app_popup_search() {  # Can only be used by subclasses of DBEntity

    $this->suppress_advanced_search = TRUE;
    $this->write_post_parm( 'simplified_search', 'Y' );

    $this->app_popup_read_calling_form_and_field();

    if( $this->publicly_available_page ) {
      $this->app_popup_title_for_public_page();
    }

    $this->db_search( $this->app_popup_get_search_table(), 
                      $this->app_get_class( $this ), 
                      $this->app_popup_get_search_results_method() );
  }
  #-----------------------------------------------------

  function app_popup_search_results() { # Can only be used by subclasses of DBEntity

    $this->app_popup_read_calling_form_and_field();

    if( $this->calling_form && $this->calling_field ) 
      $this->app_popup_add_selectform = TRUE;

    $this->write_post_parm( 'simplified_search', 'Y' );

    if( ! $this->parm_found_in_post )
      $this->write_post_parm( 'order_by', $this->app_popup_get_order_by_col() );

    $this->db_search_results();
  }
  #-----------------------------------------------------

  function app_popup_get_selectform_column() {  # Call this in db_list_columns() if needed.
                                                # E.g. columns[] = $this->app_popup_get_selectform_column().
                                                # This will give you an extra column where a 'Select' button can go.

    $selectform = array( 'column_name'      => APP_POPUP_SELECTFORM_NAME,
                         'column_label'     => 'Select',
                         'searchable'       => FALSE,
                         'is_date'          => FALSE,
                         'is_numeric'       => FALSE );
    return $selectform;
  }
  #-----------------------------------------------------

  function app_reinstate_foreign_characters( $the_value ) {

    $entity_start = '&#';

    if( ! $this->string_contains_substring( $the_value, $entity_start )) return $the_value;

    $new_value = '';
    $i = 0;

    $parts = explode( ';', $the_value );
    $partcount = count( $parts );

    foreach( $parts as $part ) {
      $i++;

      if( $this->string_contains_substring( $part, $entity_start )) {
        $entity_start_pos = strpos( $part, $entity_start );
        if( $entity_start_pos > 0 ) $new_value .= substr( $part, 0, $entity_start_pos );

        $hex = substr( $part, $entity_start_pos + strlen( $entity_start ));
        $decimal = NULL;
        $decimal = hexdec( $hex );
        if( $this->is_integer( $decimal, $allow_negative = FALSE, $allow_null = FALSE )) {
          $new_entity = $entity_start . $decimal . ';';
          $char = mb_convert_encoding( $new_entity, $this->get_character_encoding(), 'HTML-ENTITIES');
        }
        else {  # not a valid numeric entity: don't try to convert
          $char = $entity_start . $hex . ';';
        }
 
        $new_value .= $char;
      }
      else {
        $new_value .= $part;
        if( $i < $partcount ) $new_value .= ';';
      }
    }

    return $new_value;
  }
  #-----------------------------------------------------
  function app_get_unicode_signature() {  # 'Unicode signature' a.k.a. 'Byte Order Mark' or 'BOM'

    $bom = '';
    if( strtoupper( $this->get_character_encoding() ) == 'UTF-8' )
      $bom = chr(239) . chr(187) . chr(191);
    return $bom;
  }
  #-----------------------------------------------------
}
?>
