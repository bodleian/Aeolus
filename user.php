<?php
/*
 * PHP class for handling users, logins, etc.
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */

require_once 'dbentity.php';

class User extends DBEntity {

  var $username            ;
  var $md5_username        ;
  var $md5_pw              ;
  var $active              ;

  var $existing_session_found = FALSE;
  var $username_and_pw_entered = FALSE;
  var $login_token_requested = FALSE;
  var $login_token = NULL;

  var $menu_obj;  # used for writing standard page headers/footers

  #-----------------------------------------------------

  function User( &$db_connection ) { 

    #-------------------------------------------------------------------
    # Check we have got a valid (but minimal) connection to the database
    # Passed in by first script called from Apache document root.
    #-------------------------------------------------------------------
    $this->DBEntity( $db_connection );

    $this->menu_obj = new Menu( $this->db_connection );
  }
  #-----------------------------------------------------

  function set_user( $username = NULL, $being_edited_by_logged_in_user = FALSE ) {
    
    $this->clear(); # clear all properties except DB connection
    if( $username == NULL ) return NULL;

    $funcname = $this->db_database_function_name( 'select_user' );
    $statement = "select $funcname( '$username' )";  # selects *, but password is blanked out

    $this->user_row = NULL;
    $this->user_row = $this->db_select_one_value( $statement );
    if( ! $this->user_row ) die( 'User details not found.' );

    $this->parse_user_row();

    $this->person_name = $this->get_person_name();

    $funcname = $this->db_database_function_name( 'select_user_roles' );
    $statement = "select $funcname( '$username' )";
    $this->user_roles = $this->db_select_one_value( $statement );

    $funcname = $this->db_database_function_name( 'select_user_role_ids' );
    $statement = "select $funcname( '$username' )";
    $this->user_role_ids = $this->db_select_one_value( $statement );

    if( ! $being_edited_by_logged_in_user ) {
      $this->write_session_parm( 'user_roles',     $this->user_roles );
      $this->write_session_parm( 'user_role_ids',  $this->user_role_ids );
      $this->write_session_parm( 'user_email',     $this->email );
    }

    $this->being_edited_by_logged_in_user = $being_edited_by_logged_in_user;
    return $this->username;
  }
  #-----------------------------------------------------

  function parse_user_row() {

    # You get the data as one comma-separated string enclosed by round brackets, in same order as columns in table.
    # Some fields will also be surrounded by double quotes, e.g. if they contain a comma in their data.

    # strip off round brackets
    $this->user_row = substr( $this->user_row, 1 );
    $this->user_row = substr( $this->user_row, 0, strlen($this->user_row)-1 );

    # If table is altered, amend contents of this array to reflect new structure
    $user_cols = array(
      'username',
      'pw',
      'surname',
      'forename',
      'failed_logins',
      'login_time',
      'prev_login',
      'active',
      'email'
    );

    foreach( $user_cols as $colname ) {

      $first_char = substr( $this->user_row, 0, 1 );

      if( $first_char == '"' ) {
        $this->user_row = substr( $this->user_row, 1 );
        $next_quote_pos = strpos( $this->user_row, '"' );
        $col_value = substr( $this->user_row, 0, $next_quote_pos );

        if( strlen( $this->user_row ) > $next_quote_pos + 2 )  # allow for a comma following the closing double quote
          $this->user_row = substr( $this->user_row, $next_quote_pos + 2 ); 
        else
          $this->user_row = '';
      }
      else {

        $next_comma_pos = strpos( $this->user_row, ',' );

        if( $next_comma_pos > 0 ) {
          $col_value = substr( $this->user_row, 0, $next_comma_pos );
          $this->user_row = substr( $this->user_row, $next_comma_pos + 1 );
        }
        else {
          # Comma position 0 could indicate: 
          # EITHER no more commas left 
          # OR comma at start of data i.e. this column NULL
 
          $first_char = substr( $this->user_row, 0, 1 );

          if( $first_char == ',' ) {  # this column is null
            $col_value = NULL;
            $this->user_row = substr( $this->user_row, 1 );
          }
          else { # no more commas left
            $col_value = $this->user_row;
            $this->user_row = '';
          }
        }
      }

      $this->$colname = $col_value;
    }
  }
  #-----------------------------------------------------

  function get_login_parms() {

    $this->username      = $this->read_session_parm(  'username'  );

    $this->session_token = $this->read_post_parm(  SESSION_TOKEN_FIELD  );
    if( ! $this->session_token ) $this->session_token = $this->read_get_parm(  SESSION_TOKEN_FIELD  );
    if( ! $this->session_token ) $this->session_token = $this->read_session_parm(  'session_token'  );

    $this->md5_username  = $this->read_post_parm(  MD5_USERNAME_FIELD  );
    $this->md5_pw        = $this->read_post_parm(  MD5_PASSWORD_FIELD  );

    $this->login_token_requested = $this->read_post_parm(  LOGIN_REQUEST  );

    $this->existing_session_found = FALSE;
    $this->username_and_pw_entered = FALSE;

    if( $this->username && $this->session_token )
      $this->existing_session_found = TRUE;
    elseif( $this->md5_username && $this->md5_pw ) 
      $this->username_and_pw_entered = TRUE;
  }
  #-----------------------------------------------------

  function check_md5_username_and_pw() {

    $this->login_token = $this->read_session_parm( 'login_token' );
    $this->write_session_parm( 'login_token',  NULL);

    $funcname = $this->db_database_function_name( 'check_login_creds' );
    $statement = "select $funcname('" . $this->md5_username  . "', '"
                                                             . $this->md5_pw        . "', '"
                                                             . $this->login_token   . "')";

    $login_result = $this->db_select_one_value( $statement );
    return $login_result;
  }
  #-----------------------------------------------------

  function login() {

    $this->is_logged_in = FALSE;

    $this->get_login_parms();

    if( $this->existing_session_found ) {
      $session_result = $this->check_session();
      if( $session_result == SESSION_OK ) {
        $this->let_user_in();
      }
      elseif( $session_result == SESSION_TIMED_OUT ) {
        $this->login_again( 'Sorry, your session has timed out. Please log in again.' );
      }
      elseif( $session_result == SESSION_NOT_FOUND ) {
        $this->login_again( 'You may have tried to retrieve a page which is no longer available because you visited '
                            . ' it in an earlier session which has now been deleted. Old sessions are removed '
                            . ' for security reasons. Please log in now.' );
      }
    }

    elseif( $this->username_and_pw_entered ) {
      $login_result = $this->check_md5_username_and_pw();
      if( $login_result == LOGIN_SUCCESS ) {
        $this->decode_username();  
        $this->let_user_in();
      }
      else
        $this->login_err_display( $login_result );
    }

    elseif( $this->login_token_requested )
      $this->get_md5_username_and_pw();

    else
      $this->request_login_token();
      
  }
  #-----------------------------------------------------

  function request_login_token( $errmsg = NULL, $suppress_header = FALSE ) {

    $this->login_page_start( 'write_javascript_test_functions', $suppress_header ); # check Javascript is enabled

    html::form_start();

    if( $errmsg ) {
      html::bold_start();
      echo $errmsg;
      html::bold_end();
    }
    else
      echo 'Access is permitted only to authorised users of the ' . CFG_SYSTEM_TITLE . '.';
    html::new_paragraph();

    html::hidden_field( LOGIN_REQUEST, $this->create_login_request() );

    html::hidden_field( 'focus_form',  'user_login' );
    html::hidden_field( 'focus_field', 'raw_usr' );

    html::submit_button( 'login_button', 'Log in', 1 /*tabindex*/, ' onclick="check_js_enabled()" ' );

    $this->remember_get_parms(); # In case a record ID has been passed in, then after login
                                 # you can go straight to that record.

    html::form_end();

    $this->login_page_end();
  }
  #-----------------------------------------------------

  function check_javascript_enabled() {

    $login_request = $this->read_session_parm( 'login_request' );

    if( $this->login_token_requested != md5( $login_request ))
      return FALSE;
    else
      return TRUE;
  }
  #-----------------------------------------------------

  function write_javascript_test_functions() {

    echo '<head>' . NEWLINE;

    echo '<script src="md5.js">' . NEWLINE;
    echo '</script>' . NEWLINE;

    echo '<script type="text/javascript">' . NEWLINE;
    echo '<!--' . NEWLINE;

    echo 'function check_js_enabled() {' . NEWLINE;

    echo "var login_request = document.getElementById( '" . LOGIN_REQUEST . "' )" . NEWLINE;
    echo 'login_request.value = hex_md5( login_request.value )' . NEWLINE;

    echo '}' . NEWLINE;
    echo '// -->' . NEWLINE;
    echo '</script>' . NEWLINE;

    html::write_stylesheet();

    echo '</head>' . NEWLINE;

  }
  #-----------------------------------------------------

  function write_javascript_password_functions() {

    echo '<head>' . NEWLINE;

    echo '<script src="md5.js">' . NEWLINE;
    echo '</script>' . NEWLINE;

    echo '<script type="text/javascript">' . NEWLINE;
    echo '<!--' . NEWLINE;

    echo 'function hidepass() {' . NEWLINE;

    echo "var raw_use  = document.getElementById( '" . RAW_USERNAME_FIELD . "' )" . NEWLINE;
    echo "var raw_pass = document.getElementById( '" . RAW_PASSWORD_FIELD . "' )" . NEWLINE;
    echo "var md5_use  = document.getElementById( '" . MD5_USERNAME_FIELD . "' )" . NEWLINE;
    echo "var md5_pass = document.getElementById( '" . MD5_PASSWORD_FIELD . "' )" . NEWLINE;
    echo "var token= document.getElementById( '" . LOGIN_TOKEN_FIELD . "' )" . NEWLINE;

    echo 'md5_use.value  = hex_md5( hex_md5( raw_use.value ) + token.value )' . NEWLINE;
    echo 'md5_pass.value = hex_md5( hex_md5( raw_pass.value ) + token.value )' . NEWLINE;

    echo 'raw_use.value = ""' . NEWLINE;
    echo 'raw_pass.value = ""' . NEWLINE;
    echo 'token.value = ""' . NEWLINE;  # clearing this after use, so cannot so easily be sniffed out

    echo '}' . NEWLINE;
    echo '// -->' . NEWLINE;
    echo '</script>' . NEWLINE;

    html::write_stylesheet();

    echo '</head>' . NEWLINE;

  }
  #-----------------------------------------------------

  function get_md5_username_and_pw() {

    $this->login_page_start( 'write_javascript_password_functions' );  # Javascript to encrypt password etc

    if( ! $this->check_javascript_enabled()) {  # may have timed out, try again
      html::bold_start();
      echo 'Sorry, it seems you were not quick enough logging in, and the system has timed out. Please try again.';
      html::bold_end();

      $this->request_login_token( NULL, TRUE );
      return;
    }

    html::form_start( NULL, NULL, $form_name='user_login' );

    html::hidden_field( LOGIN_TOKEN_FIELD, $this->create_login_token() );

    html::table_start( ' cellspacing="10" ' );
    html::tablerow_start();
    $in_table = TRUE;

    html::input_field( RAW_USERNAME_FIELD, 'User name:', NULL /*value*/, $in_table, 
                       $size = $this->get_username_field_size() );

    html::tablerow_end();
    html::tablerow_start();

    html::password_field( RAW_PASSWORD_FIELD, 'Password:', $in_table );

    html::hidden_field( MD5_USERNAME_FIELD, '' );
    html::hidden_field( MD5_PASSWORD_FIELD, '' );

    html::tablerow_end();
    html::table_end();

    html::submit_button( 'login_button', 'Log in', /*tabindex*/ 1, ' onclick="hidepass()" ' );

    $this->carry_forward_get_parms(); # In case a record ID has been passed in, then after login
                                      # you can go straight to that record.
    html::form_end();

    $this->login_page_end();
  }
  #-----------------------------------------------------
  function get_username_field_size() {
    return NULL;
  }
  #-----------------------------------------------------

  function create_login_request() {

    $this->write_session_parm( 'login_request',  md5( uniqid( rand(), TRUE )));
    return $this->read_session_parm( 'login_request' );
  }
  #-----------------------------------------------------

  function create_login_token() {

    $this->write_session_parm( 'login_token',  md5( uniqid( rand(), TRUE )));
    return $this->read_session_parm( 'login_token' );
  }
  #-----------------------------------------------------

  function create_session_token() {

    $this->write_session_parm(  'session_token',  md5( uniqid( rand(), TRUE )));
    return $this->read_session_parm( 'session_token' );
  }
  #-----------------------------------------------------

  function login_err_display( $login_result ) {

    if( $login_result == LOGIN_FAILED_BAD_USR_OR_PW )
      $this->request_login_token( $errmsg = 'Invalid username or password entered.' );

    elseif( $login_result == LOGIN_FAILED_ACCOUNT_SUSPENDED ) {
      $this->login_page_start();
      echo 'Your account has been suspended. It can be re-activated by a member of technical support staff.';
      html::new_paragraph();
      $this->login_page_end();
    }
  }
  #-----------------------------------------------------

  function login_page_start( $write_javascript = NULL, $suppress_header = FALSE ) {

    $this->expire_page();
    html::html_start();
    html::html_head_start();

    if( $write_javascript ) $this->$write_javascript(); # Javascript to encrypt password etc
    if( $suppress_header ) return;

    $this->menu_obj->page_body_start();
    echo NEWLINE;

    $this->menu_obj->page_head( 'User Login',   # Just some displayed text, not the HTML <head>
                                TRUE );         # suppress breadcrumb trail

    echo $this->get_datetime_now_in_words();

    html::new_paragraph();
  }
  #-----------------------------------------------------

  function login_page_end() {

    $this->menu_obj->page_foot( $suppress_breadcrumbs = TRUE );
    $this->menu_obj->page_body_end();

    html::html_end();
  }
  #-----------------------------------------------------

  function is_logged_in() {
    return $this->is_logged_in;
  }
  #-----------------------------------------------------

  function decode_username() {
    $funcname = $this->db_database_function_name( 'decode_username' );
    $statement = "select $funcname( '" . $this->md5_username  . "', '" . $this->login_token . "')";
    $this->username = $this->db_select_one_value( $statement );
  }
  #-----------------------------------------------------

  function let_user_in() {
 
    $this->write_session_parm( 'username',  $this->username);

    $old_session_token = $this->session_token;  # for use with timeouts
    if( ! $old_session_token )
      $new_session_token = $this->create_session_token();

    $session_saved = $this->save_session( $old_session_token, $new_session_token );
    if( ! $session_saved ) die( GENERIC_ERR_MSG );

    $this->set_db_connection( $this->username );
    $this->set_user( $this->username );
    $this->is_logged_in = TRUE;

    # The parameters that may originally have been passed via GET have been written to hidden fields
    # and so will now be in POST. Write them back into GET.
    $this->reinstate_get_parms();
  }
  #-----------------------------------------------------
  function set_db_connection( $the_username ) {
    $this->db_connection = new DBQuery( $the_username );
  }
  #-----------------------------------------------------

  function check_session() {

    $funcname = $this->db_database_function_name( 'check_session' );
    $statement = "select $funcname( '$this->username', '$this->session_token' )";
    $session_result = $this->db_select_one_value( $statement );
    return $session_result;
  }
  #-----------------------------------------------------

  function save_session( $old_session_token, $new_session_token ) {

    $funcname = $this->db_database_function_name( 'save_session_data' );
    $statement = "select $funcname("
               . " '$this->username', '$old_session_token', '$new_session_token' )";
    $session_saved = $this->db_select_one_value( $statement );
    return $session_saved;
  }
  #-----------------------------------------------------

  function login_again( $errmsg = NULL ) {

    $this->write_session_parm( 'username',  NULL);
    $this->write_session_parm( 'session_token',  NULL);

    $this->request_login_token( $errmsg ); 
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {

    switch( $parm_name ) {

      case 'username':

        return $this->is_alphanumeric_or_blank( $this->parm_value, 
                                                $allow_underscores = TRUE, 
                                                $allow_all_whitespace = FALSE );

      case 'edited_roles':
        if( $this->parm_value == NULL )
          return TRUE;
        else
          return $this->is_array_of_integers( $this->parm_value, $allow_negatives = TRUE );

      case 'user_roles':
      case 'user_role_ids':
        return parent::validate_parm( $parm_name );

      case 'forename':
      case 'surname':
      case 'email':
        return $this->is_ok_free_text( $this->parm_value );

      case 'active':
        return $this->is_on_off_switch( $this->parm_value );

      case 'confirm_deletion':
        if( $this->parm_value == 'Y' || $this->parm_value == '' )
          return TRUE;
        else
          return FALSE;

      default:
        # If a record ID has been passed in, then go to that record after successful login
        if( $this->string_starts_with( $parm_name, GET_RECORD_AFTER_LOGIN_PREFIX )) { 

          # Field *name* must be a valid column name of the type we use in our database,
          # i.e. alphanumeric plus underscores
          if( ! $this->is_alphanumeric( $parm_name, $allow_underscores = TRUE )) 
            return FALSE;

          # Field *value* must be a valid key value for our database, i.e. integer, or possibly 
          # an HTML ID i.e. alphanumeric plus underscores, hyphens, colons, starting with a letter.
          if( ! $this->is_integer( $this->parm_value ) && ! $this->is_html_id( $this->parm_value ))
            return FALSE;

          return TRUE;
        }

        # All other fields need to contain an MD5 value.
        return $this->is_md5( $this->parm_value );
    }
    return FALSE;
  }
  #-----------------------------------------------------

  function get_person_name() {

    return trim( $this->forename . ' ' . $this->surname );
  }
  #-----------------------------------------------------

  function get_login_time() {

    return substr( $this->login_time, 0, 16  );
  }
  #-----------------------------------------------------

  function get_prev_login() {

    return substr( $this->prev_login, 0, 16  );
  }
  #-----------------------------------------------------

  function die_if_not_supervisor() {

    # They should already be prevented from getting here by menu option restrictions, 
    # but a little extra carefulness costs nothing...

    if( ! $this->user_is_supervisor())
      $this->die_on_error( 'You do not have permission to access this option' );
  }
  #-----------------------------------------------------

  function browse_users() {

    $this->die_if_not_supervisor();

    $statement = 'select * from ' . $this->db_users_and_roles_viewname();
    $existing_users = $this->db_select_into_array( $statement );
    $last_user = '0';  # a dummy value which won't exist in the database
    $all_roles = '';

    html::table_start( ' class="widelyspacepadded" ' );
    html::tablerow_start();

    html::tabledata_start( 'class="highlight1"' );  # Menu
    html::form_start( 'user', 'edit_user1_other' );
    html::submit_button( 'add_button', 'Add' );
    html::form_end();
    html::new_paragraph();

    html::form_start( '', '' );
    html::submit_button( 'cancel_button', 'Cancel' );
    html::form_end();
    html::tabledata_end();

    html::tabledata_start();  # List of users

    if( ! is_array( $existing_users )) 
      echo 'No existing users found.';
    else {
      html::table_start( ' class="datatab spacepadded" ' );

      html::table_head_start();
      html::tablerow_start();
      html::column_header( 'Username' );
      html::column_header( 'Role(s)' );
      html::column_header( 'Active' );
      html::column_header( 'Forename' );
      html::column_header( 'Surname' );
      html::column_header( 'Email address' );
      html::tablerow_end();
      html::table_head_end();

      html::table_body_start();
      foreach( $existing_users as $row ) {
        if( $row['username'] != $last_user && $last_user != '0' ) {
          html::tablerow_start();

          html::tabledata_start( 'class="highlight2"' ); 
          html::form_start( 'user', 'edit_user1_other' );
          echo $username  . LINEBREAK;
          html::hidden_field( 'username', $username );
          html::submit_button( 'edit_button', 'Edit' );
          html::form_end();
          html::tabledata_end();

          html::tabledata( $all_roles );
          html::tabledata( $active?'Yes':'No' );
          html::tabledata( $forename );
          html::tabledata( $surname );
          html::tabledata( $email );
          html::tablerow_end();

          $all_roles = '';
        }

        $last_user = $row[ 'username' ];
        extract( $row );

        if( $all_roles ) $all_roles .= LINEBREAK;
        $all_roles .= $role_name;
      }

      html::tablerow_start();
      html::tabledata_start( 'class="highlight2"' ); 
      html::form_start( 'user', 'edit_user1_other' );
      echo $username  . LINEBREAK;
      html::hidden_field( 'username', $username );
      html::submit_button( 'edit_button', 'Edit' );
      html::form_end();
      html::tabledata_end();

      html::tabledata( $all_roles );
      html::tabledata( $active?'Yes':'No' );
      html::tabledata( $forename );
      html::tabledata( $surname );
      html::tabledata( $email );
      html::tablerow_end();

      html::table_body_end();
      html::table_end();
    }

    html::tabledata_end();
    html::tablerow_end();
    html::table_end();

    html::new_paragraph();
    html::italic_start();
    echo 'Note: if a user has repeatedly entered the wrong password, their account will be deactivated and they '
         . ' will not be able to log in. You can restore their login rights by clicking Edit and setting "Active"'
         . ' to Yes.';
    html::new_paragraph();

    echo 'All users who wish to produce reports as a CSV file should have an email address entered, otherwise '
         . ' the CSV file cannot be sent to them.';
    html::italic_end();
  }
  #-----------------------------------------------------

  function get_simple_input_fields() {

    $fields = array( 'username' => 'Username*',
                     'forename' => 'Forename',
                     'surname'  => 'Surname*',
                     'active'   => 'Active',
                     'email'    => 'Email address' );
    return $fields;
  }
  #-----------------------------------------------------
  function edit_user1_self( $username = NULL ) {
    $this->edit_user1( $this->db_get_username(), $editing_self = TRUE );
  }
  #-----------------------------------------------------
  function edit_user1_other( $username = NULL ) {
    $this->edit_user1( $username, $editing_self = FALSE );
  }
  #-----------------------------------------------------

  function edit_user1( $username = NULL, $editing_self = FALSE ) {

    $next_method = 'edit_user2_';
    if( $editing_self ) 
      $next_method .= 'self';
    else {
      $next_method .= 'other';
      $this->die_if_not_supervisor();
    }

    $new_record = FALSE;
    $edit_user = new User( $this->db_connection );

    if( ! $username ) $username = $this->read_post_parm( 'username' );
    if( $username && ! $this->parm_found_in_post( 'new_record' )) {
      $edit_user->set_user( $username, $being_edited_by_logged_in_user = TRUE );
    }
    else {
      $new_record = TRUE;
      $edit_user->active = TRUE;
      $sequence_name = $this->get_system_prefix() . '_users_username_seq';
      $statement = "select nextval( '$sequence_name'::regclass )";
      $edit_user->username = $edit_user->db_select_one_value( $statement );
      $edit_user->username = str_pad( $edit_user->username, 3, '0', STR_PAD_LEFT );
      $edit_user->username = $this->get_system_prefix() . $edit_user->username;
    }

    if( $new_record ) {
      html::italic_start();
      echo 'Note: you can change the default username given below if you wish, but any alternative '
           . ' username MUST begin with "' . $this->get_system_prefix() . '" plus at least one other character.';
      html::new_paragraph();
      echo 'In addition, usernames can ONLY contain letters, numbers and the underline character.';
      html::new_paragraph();
      echo 'New username must not duplicate existing ones (shown below).';
      html::italic_end();
      html::new_paragraph();
    }

    $fields = $this->get_simple_input_fields();

    html::table_start( ' class="widelyspacepadded boxed" ' );
    html::tablerow_start();

    html::tabledata_start( 'class="highlight1 bold"' );  # Menu

    #-----------------------
    # Start "save user" form
    #-----------------------
    html::form_start( 'user', $next_method );
    if( $new_record ) {
      $save_key_name = 'Save new user';
      html::hidden_field( 'new_record', 1 );
    }
    else
      $save_key_name = 'Save and end';
    echo $save_key_name . LINEBREAK;
    html::submit_button( 'save_button', 'OK' );

    if( ! $new_record ) {
      html::new_paragraph();
      html::horizontal_rule();
      html::new_paragraph();
      echo 'Set password' . LINEBREAK;
      html::submit_button( 'set_password_button', 'OK' );

      # "Editing self" flag only shows the route you took through the menu (i.e. "Edit your own details" option),
      # so need to check whether a supervisor is trying to delete their own self and prevent this.
      # Also don't allow deletion of the "primary" supervisor.
      if( $this->user_is_supervisor() && $this->db_get_username() != $username 
      && $username != $this->get_supervisor() ) {
        html::new_paragraph();
        html::horizontal_rule();
        html::new_paragraph();
        echo 'Delete user' . LINEBREAK;
        html::submit_button( 'delete_user_button', 'OK' );
      }
    }

    html::new_paragraph();
    html::new_paragraph();
    html::horizontal_rule();
    html::new_paragraph();
    echo 'Cancel' . LINEBREAK;
    html::submit_button( 'cancel_button', 'OK' );

    html::tabledata_end();

    html::tabledata_start();  # List of users

    html::table_start();

    foreach( $fields as $field_name => $field_label ) {
      if( $field_name == 'active' && ( $editing_self || $this->db_get_username() == $username )) {
        html::hidden_field( 'active', $edit_user->active );
        continue;
      }

      html::tablerow_start();

      $field_size = NULL;
      if( $field_name == 'email' ) $field_size = 60;

      $label_parms = NULL;
      if( substr( $field_label, -1 ) == '*' ) $label_parms = 'class="bold"';

      if( $field_name == 'username' && ! $new_record ) {
        html::tabledata( $field_label, 'class="bold "' );
        html::tabledata( $username, 'class="bold highlight2"' );
        html::hidden_field( 'username', $edit_user->username );
      }

      elseif( $field_name == 'active' ) {
        $this->yes_no_dropdown_int( $field_name, $field_label, $edit_user->active, $in_table = TRUE );
      }

      else {
        html::input_field( $field_name, $field_label, $edit_user->$field_name, $in_table = TRUE, $field_size,
                           $tabindex=1, $label_parms );
      }

      html::tablerow_end();
    }

    html::tablerow_start();
    if( $this->user_is_supervisor()) $edit_user->edit_user_roles( $username );
    html::tablerow_end();

    html::table_end();
    html::form_end();

    html::tabledata_end();

    if( $new_record ) {
      html::tabledata_start( 'class="boxed"' );
      echo 'Existing users:';
      html::new_paragraph();
      $statement = 'select distinct username, forename, surname from ' . $this->db_users_and_roles_viewname()
                 . ' order by username';
      $existing_users = $this->db_select_into_array( $statement );
      if( is_array( $existing_users )) {
        html::ulist_start();
        foreach( $existing_users as $row ) {
          html::listitem( $row['username'] . ': ' . $row['forename'] . ' ' . $row['surname'] );
        }
        html::ulist_end();
      }
      else
        echo 'Existing users: none';
      html::tabledata_end();
    }

    html::tablerow_end();
    html::table_end();
  }
  #-----------------------------------------------------

  function edit_user_password_own( $username = NULL ) { # username *here* is just a dummy parameter, ignore it.
    $this->edit_user_password( $this->db_get_username(), $editing_self = TRUE );
  }
  #-----------------------------------------------------

  function edit_user_password_other( $username = NULL ) {
    $this->edit_user_password( $username, $editing_self = FALSE );
  }
  #-----------------------------------------------------
  #=================================================================
  # N.B. DO NOT USE THIS FUNCTION OUTSIDE THE UNIVERSITY FIREWALL!!! 
  # (Have to submit new password as simple MD5!)
  #=================================================================

  function edit_user_password( $username = NULL, $editing_self = FALSE ) {

    if( ! $editing_self ) $this->die_if_not_supervisor();

    if( ! $username ) $username = $this->read_post_parm( 'username' );
    $edit_user = new User( $this->db_connection );
    $username = $edit_user->set_user( $username, $being_edited_by_logged_in_user = TRUE );

    if( ! $username ) $this->die_on_error( 'Invalid username.' );

    html::h3_start();
    echo "Set password for user '";
    echo $edit_user->username . "': ";
    echo $edit_user->forename . ' ' . $edit_user->surname;
    html::h3_end();
    html::new_paragraph();

    #---------------------
    # Display user details
    #---------------------
    $edit_user->display_user_roles( $username );
    html::new_paragraph();

    #-----------
    # Start form
    #-----------
    $method_name = 'save_user_password_';
    if( $editing_self )
      $method_name .= 'own';
    else
      $method_name .= 'other';
    $this->form_name = html::form_start( 'user', $method_name ); 

    #--------------------------------------
    # Write "Hide Password" Javascript here
    #--------------------------------------
    echo '<script src="md5.js">' . NEWLINE;
    echo '</script>' . NEWLINE;

    $funcvars = array(
      RAW_AUTHORISING_NAME_VAR => RAW_AUTHORISING_NAME_FIELD,
      RAW_AUTHORISING_PASS_VAR => RAW_AUTHORISING_PASSFIELD,
      MD5_AUTHORISING_NAME_VAR => MD5_AUTHORISING_NAME_FIELD,
      MD5_AUTHORISING_PASS_VAR => MD5_AUTHORISING_PASSFIELD,
      AUTHORISE_TOKEN_VAR      => AUTHORISING_TOKEN_FIELD,

      RAW_EDITED_NAME_VAR => RAW_EDITED_NAME_FIELD,
      RAW_EDITED_PASSVAR1 => RAW_EDITED_PASSFIELD1,
      RAW_EDITED_PASSVAR2 => RAW_EDITED_PASSFIELD2,
      MD5_EDITED_NAME_VAR => MD5_EDITED_NAME_FIELD,
      MD5_EDITED_PASSVAR1 => MD5_EDITED_PASSFIELD1,
      MD5_EDITED_PASSVAR2 => MD5_EDITED_PASSFIELD2 
    );

    $func = 'function prepare_values() {' . NEWLINE;

    foreach( $funcvars as $js_var => $form_field ) {  # get form fields into more easily handled variables
      $func .= "  var $js_var = document.getElementById( '$form_field' )" . NEWLINE;
    }

    $func .= '  ' . MD5_EDITED_NAME_VAR . '.value = hex_md5( hex_md5( ' . RAW_EDITED_NAME_VAR 
             . '.value ) + ' . AUTHORISE_TOKEN_VAR . '.value )' . NEWLINE;

    $func .= '  ' . MD5_EDITED_PASSVAR1 . '.value = hex_md5( ' . RAW_EDITED_PASSVAR1 . '.value )' . NEWLINE;

    # Treat password version 2 differently from password version 1
    # so that they don't look the same to a password sniffer
    $func .= '  ' . MD5_EDITED_PASSVAR2 . '.value = hex_md5( hex_md5( ' . RAW_EDITED_PASSVAR2 
             . '.value ) + ' . AUTHORISE_TOKEN_VAR . '.value )' . NEWLINE;

    $func .= '  ' . MD5_AUTHORISING_NAME_VAR . '.value = hex_md5( hex_md5( ' . RAW_AUTHORISING_NAME_VAR 
             . '.value ) + ' . AUTHORISE_TOKEN_VAR . '.value )' . NEWLINE;
    $func .= '  ' . MD5_AUTHORISING_PASS_VAR . '.value = hex_md5( hex_md5( ' . RAW_AUTHORISING_PASS_VAR 
             . '.value ) + ' . AUTHORISE_TOKEN_VAR . '.value )' . NEWLINE;

    $funcvars = NULL;

    $funcvars = array(
      RAW_AUTHORISING_NAME_VAR => RAW_AUTHORISING_NAME_FIELD,
      RAW_AUTHORISING_PASS_VAR => RAW_AUTHORISING_PASSFIELD,
      AUTHORISE_TOKEN_VAR      => AUTHORISING_TOKEN_FIELD,
      RAW_EDITED_NAME_VAR      => RAW_EDITED_NAME_FIELD,
      RAW_EDITED_PASSVAR1      => RAW_EDITED_PASSFIELD1,
      RAW_EDITED_PASSVAR2      => RAW_EDITED_PASSFIELD2,
    );

    foreach( $funcvars as $js_var => $form_field ) {  # clear "raw" field values so can't easily be sniffed out
      $func .= "  $js_var.value = ''" . NEWLINE;
    }

    $func .=  '}' . NEWLINE;

    html::write_javascript_function( $func );

    #----------------------
    # Password entry fields
    #----------------------
    html::table_start( 'class="widelyspacepadded highlight2 contrast1_boxed"' );
    html::tablerow_start();
    html::tabledata_start( 'class="rightaligned"' );

    $this->write_authorising_token_field();

    html::hidden_field( RAW_AUTHORISING_NAME_FIELD, $this->username );
    html::hidden_field( RAW_EDITED_NAME_FIELD, $edit_user->username );

    if( $editing_self )
      $label = 'Enter your current password';
    else
      $label = 'Enter supervisor (' . $this->username . ') password';
    html::password_field( RAW_AUTHORISING_PASSFIELD, $label );
    html::new_paragraph();

    $label = 'Enter new password';
    if( ! $editing_self ) $label .= ' for ' . $edit_user->username ;
    html::password_field( RAW_EDITED_PASSFIELD1, $label );
    html::new_paragraph();

    $label = 'Re-enter new password';
    if( ! $editing_self ) $label .= ' for ' . $edit_user->username ;
    html::password_field( RAW_EDITED_PASSFIELD2, $label );
    html::new_paragraph();

    html::hidden_field( MD5_AUTHORISING_PASSFIELD, '' );
    html::hidden_field( MD5_AUTHORISING_NAME_FIELD, '' );
    html::hidden_field( MD5_EDITED_PASSFIELD1, '' );
    html::hidden_field( MD5_EDITED_PASSFIELD2, '' );
    html::hidden_field( MD5_EDITED_NAME_FIELD, '' );

    html::tabledata_end();
    html::new_tablerow();

    html::tabledata_start();
    html::submit_button( 'save_button', 'Save', /*tabindex*/ 1, ' onclick="prepare_values()" ' );
    html::submit_button( 'cancel_button', 'Cancel' );
    html::tabledata_end();

    html::tablerow_end();
    html::table_end();
    
    html::form_end();
  }
  #-----------------------------------------------------

  function edit_user_roles( $username = NULL ) {

    $this->die_if_not_supervisor();

    $this->field_name = 'edited_roles';
    $this->field_label = 'Role(s):';

    $this->db_select_from_multiple_checkboxes( $lookup_table = $this->db_roles_tablename(), 
                                               $lookup_id_col = 'role_id', 
                                               $lookup_display_col = 'role_name',
                                               $detail_table = $this->db_users_and_roles_viewname(), 
                                               $detail_id_col_1 = 'username', 
                                               $detail_id_col_2 = 'role_id', 
                                               $order_by_col = 'role_name', 
                                               $in_table = TRUE,
                                               $detail_id_col_1_is_text = TRUE );
  }
  #-----------------------------------------------------

  function save_user_password_own() {
    $this->save_user_password( $editing_self = TRUE );
  }
  #-----------------------------------------------------

  function save_user_password_other() {
    $this->save_user_password( $editing_self = FALSE );
  }
  #-----------------------------------------------------

  function save_user_password( $editing_self = FALSE ) {

    if( $this->parm_found_in_post( 'cancel_button' )) {
      echo 'Password change was cancelled.';
      html::new_paragraph();
      if( $editing_self ) 
        $this->edit_user1_self();
      else
        $this->browse_users();
      return;
    }

    $calling_method = 'edit_user_password_';
    if( $editing_self ) 
      $calling_method .= 'own';
    else {
      $this->die_if_not_supervisor();
      $calling_method .= 'other';
    }

    #-------------------------------------------------------
    # Check login credentials using "authorising" password, 
    # i.e. either supervisor password or user's own password
    #-------------------------------------------------------
    $md5_auth_name = $this->read_post_parm( MD5_AUTHORISING_NAME_FIELD );
    $md5_auth_pass = $this->read_post_parm( MD5_AUTHORISING_PASSFIELD );

    $md5_user_name  = $this->read_post_parm( MD5_EDITED_NAME_FIELD  );
    $md5_user_pass1 = $this->read_post_parm( MD5_EDITED_PASSFIELD1 );
    $md5_user_pass2 = $this->read_post_parm( MD5_EDITED_PASSFIELD2 );

    $token = $this->read_session_parm( AUTHORISING_TOKEN_FIELD );
    $this->write_session_parm( AUTHORISING_TOKEN_FIELD,  NULL);

    $funcname = $this->db_database_function_name( 'check_login_creds' );
    $statement = "select $funcname('" 
               .   $md5_auth_name  . "', '"
               .   $md5_auth_pass  . "', '"
               .   $token   . "')";

    $authorising_connection = new DBQuery ( CONSTANT_MINIMAL_USER );
    $authorising_user = new User( $authorising_connection );
    $authorisation_check = $authorising_user->db_select_one_value( $statement );

    if( $authorisation_check != LOGIN_SUCCESS ) $this->die_on_error( 'Invalid authorising user/password.' );

    $authorising_user->login_token = $token;  # used by "decode username"
    $authorising_user->md5_username = $md5_auth_name;  # used by "decode username"
    $authorising_user->decode_username();
    if( $authorising_user->username != $this->db_get_username()) 
      $this->die_on_error( 'Invalid authorising user/password');

    $authorising_user->clear();
    $authorising_user->login_token = $token;  # used by "decode username"
    $authorising_user->md5_username = $md5_user_name;  # used by "decode username"
    $authorising_user->decode_username();
    $username = $authorising_user->username;

    $authorising_connection = NULL;
    $authorising_user = NULL;

    if( $editing_self && $username != $this->db_get_username())
      $this->die_on_error( 'Invalid authorising user/password.');

    #------------------------------------------------------------
    # We are now happy that the "authorising" password is OK, so: 
    # 1. Validate new password.
    # 2. Set new password.
    #------------------------------------------------------------
    # Validate
    #---------
    $failed_validation = FALSE;
    $errmsg = NULL;
    if( md5( $md5_user_pass1 . $token ) !=  $md5_user_pass2 ) {
      $failed_validation = TRUE;
      $errmsg = 'New password version 1 did not match new password version 2. Please re-enter.';
    }

    if( ! $failed_validation ) {
      switch( $md5_user_pass1 ) {  # can't check every whitespace combination, but try a few
        case md5(''):
        case md5(' '):
        case md5("\t"):
        case md5("\n"):
        case md5("\r"):
        case md5("\0"):
          $failed_validation = TRUE;
          $errmsg = 'New password was blank. Please re-enter.';

        default:
          break;
      }
    }

    if( $failed_validation ) {
      html::div_start( 'class="errmsg"' );
      echo $errmsg;
      html::div_end();
      html::new_paragraph();
      $this->$calling_method( $username );
      return;
    }

    #----------------------
    # Save changed password
    #----------------------
    $funcname = $this->db_database_function_name( 'set_pw_by_super' );
    $statement = "select $funcname( '" 
               . $md5_user_name . "', '"
               . $token . "', '"
               . $md5_user_pass1 . "')";

    if( $this->user_is_supervisor ) 
      $confirm = $this->db_select_one_value( $statement );

    else { # need to connect as a supervisor to run this query
      #---------------------------------------------------------------------------
      # N.B. We must create a username of a predictable pattern for each DB
      # otherwise password change option will not work.
      #---------------------------------------------------------------------------
      $supervisor_name = $this->get_supervisor(); 
      $authorising_connection = new DBQuery ( $supervisor_name );
      $authorising_user = new User( $authorising_connection );

      $confirm = $authorising_user->db_select_one_value( $statement );

      $authorising_connection = NULL;
      $authorising_user = NULL;
    }

    if( $confirm == $username ) {
      echo 'Password has successfully been set for user "' . $username . '".';
      html::new_paragraph();
      if( $editing_self )
        $this->edit_user1_self();
      else
        $this->browse_users();
      return;
    }
    else
      $this->die_on_error( 'Error setting password for user "' . $username . '".' );
  }
  #-----------------------------------------------------

  function create_authorising_token() {

    $this->write_session_parm( AUTHORISING_TOKEN_FIELD,  md5( uniqid( rand(), TRUE )));
    return $this->read_session_parm( AUTHORISING_TOKEN_FIELD );
  }
  #-----------------------------------------------------

  function write_authorising_token_field() {

    html::hidden_field( AUTHORISING_TOKEN_FIELD, $this->create_authorising_token() );
  }
  #-----------------------------------------------------
  function edit_user2_self( $username = NULL ) {
    $this->edit_user2( $username, $editing_self = TRUE );
  }
  #-----------------------------------------------------
  function edit_user2_other( $username = NULL ) {
    $this->edit_user2( $username, $editing_self = FALSE );
  }
  #-----------------------------------------------------

  function edit_user2( $username = NULL, $editing_self = FALSE ) {

    if( ! $editing_self ) $this->die_if_not_supervisor();

    #-----------------------------------------------
    # Cancel - go either to main menu or user browse
    #-----------------------------------------------
    if( $this->parm_found_in_post( 'cancel_button' )) {
      echo 'User edit was cancelled.';
      html::new_paragraph();
      if( $this->user_is_supervisor() && ! $editing_self )
        $this->browse_users();
      else {
        $this->menu_obj->return_to_main_menu();
      }
      return;
    }

    #-------------------------------
    # Go to "Change password" screen
    #-------------------------------
    if( $this->parm_found_in_post( 'set_password_button' )) {
      $method_name = 'edit_user_password_';
      if( $editing_self )
        $method_name .= 'own';
      else
        $method_name .= 'other';
      $this->$method_name( $username );
      return;
    }

    #-------------------------------
    # Go to "Delete user" screen
    #-------------------------------
    if( $this->parm_found_in_post( 'delete_user_button' )) {
      $this->delete_user1( $username );
      return;
    }

    #-------------------------------
    # Validate and save user details
    #-------------------------------
    $username = $this->read_post_parm( 'username' );
    $surname = $this->read_post_parm( 'surname' );
    $forename = $this->read_post_parm( 'forename' );
    $email = $this->read_post_parm( 'email' );
    $active = 0;
    $edited_roles = NULL;
    if( $this->user_is_supervisor()) {
      $active = $this->read_post_parm( 'active' );
      $edited_roles = $this->read_post_parm( 'edited_roles' );
    }
    $new_record = FALSE;
    if( $this->parm_found_in_post('new_record')) $new_record = TRUE;

    $this->failed_validation = FALSE;
    $errmsg = NULL;
    if( trim( $username ) == '' ) {
      $errmsg = 'Username cannot be blank.';
      $this->failed_validation = TRUE;
    }

    if( ! $this->failed_validation ) {
      if( trim( $surname ) == '' ) {
        $errmsg = 'Surname cannot be blank.';
        $this->failed_validation = TRUE;
      }
    }

    if( ! $this->failed_validation ) {
      $system_prefix = $this->get_system_prefix();
      if( substr( $username, 0, strlen( $system_prefix )) != $system_prefix
      ||  strlen( $username ) <= strlen( $system_prefix )) {
        $errmsg = "Usernames must begin with '$system_prefix' followed by at least one more character.";
        $this->failed_validation = TRUE;
      }
    }

    if( $new_record && ! $this->failed_validation ) {
      $existing_username = NULL;
      $statement = 'select username from ' . $this->db_users_and_roles_viewname() . " where lower( username ) = '"
                 . strtolower($username) . "'";
      $existing_username = $this->db_select_one_value( $statement );
      if( $existing_username ) {
        $errmsg = "Username '$username' is already in use.";
        $this->failed_validation = TRUE;
      }
    }

    # Do not allow removal of supervisor role from primary supervisor,
    # as we need this user to exist to enable normal users to set their passwords
    $primary_supervisor = $this->get_supervisor(); 
    if( $username == $primary_supervisor ) {
      $deleting_primary_supervisor = TRUE;
      if( is_array( $edited_roles )) {
        if( in_array( SUPERVISOR_ROLE_ID, $edited_roles )) 
          $deleting_primary_supervisor = FALSE;
      }
      if( $deleting_primary_supervisor ) {
        $errmsg = "Cannot remove supervisor role from '$username': this user is the primary supervisor for your system.";
        $this->failed_validation = TRUE;
      }
    }

    # Before allowing a supervisor to delete their own supervisor role, 
    # see if this is the only supervisor for this system, and if so, force them to STAY a supervisor.
    if(( ! $this->failed_validation ) && $this->user_is_supervisor() && $this->db_get_username() == $username ) {
      $other_supervisor = NULL;
      $deleting_own_super_role = TRUE;
      if( is_array( $edited_roles )) {
        if( in_array( SUPERVISOR_ROLE_ID, $edited_roles )) 
          $deleting_own_super_role = FALSE;
      }
      if( $deleting_own_super_role ) {
        $statement = 'select username from ' . $this->db_users_and_roles_viewname()
                   . ' where role_id = ' . SUPERVISOR_ROLE_ID
                   . " and username != '$username'";
        $other_supervisor = $this->db_select_one_value( $statement );
        if( ! $other_supervisor ) {
          $errmsg = 'You are currently the only supervisor on the system, ' 
                  . 'so you cannot delete your own supervisor rights at present: '
                  . 'every system must always have at least one user with supervisor rights.';
          $this->failed_validation = TRUE;
        }
        else
          $editing_self = TRUE; # make sure that after deletion of super role, they get treated as normal user
      }
    }

    #---------------------------------------------------
    # If failed validation, put them back in edit screen
    #---------------------------------------------------
    if( $this->failed_validation ) {
      html::div_start( 'class="errmsg"' );
      $this->echo_safely( $errmsg );
      html::div_end();
      html::new_paragraph();
      $this->edit_user1( $username, $editing_self );
      return;
    }

    #------------------
    # Save user details 
    #------------------
    $username = $this->escape( $username );
    $surname = $this->escape( $surname );
    $forename = $this->escape( $forename );
    $email = $this->escape( $email );

    if( $this->user_is_supervisor()) 
      $authorising_user = $this;  # copy the current database connection etc.
    else {
      $supervisor_name = $this->get_supervisor(); 
      $authorising_connection = new DBQuery ( $supervisor_name );
      $authorising_user = new User( $authorising_connection );
    }
    
    #------------------
    # Begin transaction
    #------------------
    $authorising_user->db_run_query( 'BEGIN TRANSACTION' );

    #---------
    # New user
    #---------
    if( $new_record ) {
      $this->make_initial_password();

      $funcname = $this->db_database_function_name( 'create_user' );
      $statement = "select $funcname( "
                 . "  '$username',"
                 . "  md5('$this->initial_password'), "
                 . "  '$surname',"
                 . "  '$forename',"
                 . "  '$email',"
                 . "  'Y')";  # 'Y' = grant edit role

      $this->db_run_query( $statement );
    }

    #--------------
    # Existing user
    #--------------
    else {
      $funcname = $this->db_database_function_name( 'update_user_details' );
      $statement = "select $funcname( '" . $username . "', '"
                                         . $surname  . "', '"
                                         . $forename . "', '"
                                         . $email    . "' )";
      $authorising_user->db_run_query( $statement );

      if( $this->user_is_supervisor()) {
        if( $active == '' ) $active = '0';
        $funcname = $this->db_database_function_name( 'set_user_login_status' );
        $statement = "select $funcname( '" . $username . "', " . $active . ')';
        $authorising_user->db_run_query( $statement );
      }
    }

    #---------------------------
    # Insert and/or delete roles
    #---------------------------
    if( $this->user_is_supervisor()) {
      $role_string = '';
      if( is_array( $edited_roles )) {
        foreach( $edited_roles as $role_id ) {
          if( $role_string ) $role_string .= ', ';
          $role_string .= $role_id;
        }
      }
      else
        $role_string = '0';

      $funcname = $this->db_database_function_name( 'set_user_roles' );
      $statement = "select $funcname( '$username', '$role_string' )";
      $authorising_user->db_run_query( $statement );
    }

    #-------
    # Commit
    #-------
    $authorising_user->db_run_query( 'COMMIT' );

    $initial_password = $this->initial_password; # store it, so it doesn't get cleared
    $this->set_user( $this->username ); # refresh permissions etc
    $this->initial_password = $initial_password;

    #-----------------------------------
    # Return to main menu or user browse
    #-----------------------------------
    echo $this->get_datetime_now_in_words() . ". Changes to user '" . $username . "' have been saved.";
    if( $new_record ) {
      html::new_paragraph();
      html::bold_start();
      echo 'This user has been allocated the following initial password: ' . $this->initial_password;
      html::bold_end();
      html::new_paragraph();
      echo 'Please inform this user of their initial password, and ask them to change it on their first login. ';
      echo LINEBREAK . "(Alternatively you could change the user's password yourself"
                     . " using the 'Edit' and 'Set password' options.)";
    }

    html::new_paragraph();

    if( $this->user_is_supervisor() && ! $editing_self )
      $this->browse_users();
    else
      $this->menu_obj->return_to_main_menu();

    return;
  }
  #-----------------------------------------------------

  function make_initial_password( $randnum = NULL ) {

    $this->initial_password = $this->get_system_prefix();
    if( ! $randnum ) $randnum = rand( 100, 99999 );
    $this->initial_password .= $randnum;
  }
  #-----------------------------------------------------

  function delete_user1( $username = NULL ) {

    $this->die_if_not_supervisor();

    if( ! $username ) $username = $this->read_post_parm( 'username' );
    if( ! $username ) $this->die_on_error( 'No user was selected.' );

    $edit_user = new User( $this->db_connection );
    $edit_user->set_user( $username, $being_edited_by_logged_in_user = TRUE );

    if( ! $edit_user->username ) $this->die_on_error( 'Invalid user ID ' . $username );

    html::div_start( ' id="deletion_choice" class="warning" ' );

    html::h3_start();
    echo "DELETE user '$username'?";
    html::new_paragraph();
    echo 'Name: ' . $edit_user->forename . ' ' . $edit_user->surname;
    html::new_paragraph();
    
    $edit_user->display_user_roles( $username );
    html::h3_end();
    html::new_paragraph();
    html::horizontal_rule();
    html::new_paragraph();

    #-----------
    # Start form
    #-----------
    $this->form_name = html::form_start( 'user', 'delete_user2' );

    echo 'If you wish to delete this user, tick the checkbox below then click Delete.';
    html::new_paragraph();
    html::checkbox( 'confirm_deletion', 'Proceed with deletion', NULL, $value_when_checked = 'Y' );
    html::new_paragraph();
    html::hidden_field( 'username', $username );
    html::submit_button( 'delete_button', 'Delete' );
    html::submit_button( 'cancel_button', 'Cancel' );
    html::new_paragraph();
    html::horizontal_rule();
    html::form_end();
    html::div_end();
  }
  #-----------------------------------------------------

  function delete_user2 ( $username = NULL ) {

    $this->die_if_not_supervisor();

    if( ! $username ) $username = $this->read_post_parm( 'username' );
    if( ! $username ) $this->die_on_error( 'No user was selected.' );

    $edit_user = new User( $this->db_connection );
    $edit_user->set_user( $username, $being_edited_by_logged_in_user = TRUE );
    if( ! $edit_user->username ) $this->die_on_error( 'Invalid user ID ' . $username );

    $proceed_with_deletion = TRUE;

    if( $this->parm_found_in_post( 'cancel_button' )) {
      echo 'Deletion was cancelled.';
      $proceed_with_deletion = FALSE;
    }

    if( $proceed_with_deletion ) {
      $confirm_deletion = $this->read_post_parm( 'confirm_deletion' );
      if( $confirm_deletion != 'Y' ) {
        html::div_start( ' class="errmsg" ' );
        echo '"Proceed with deletion" checkbox was not ticked, so deletion was cancelled.';
        $proceed_with_deletion = FALSE;
        html::div_end();
      }
    }

    if( ! $proceed_with_deletion ) {
      html::new_paragraph();
      $this->edit_user1_other( $username );
      return;
    }

    $statement = 'BEGIN TRANSACTION';
    $this->db_run_query( $statement );

    $funcname = $this->db_database_function_name( 'delete_user' );
    $statement = "select $funcname( '$username' )";
    $this->db_run_query( $statement );

    $statement = 'COMMIT';
    $this->db_run_query( $statement );

    echo "User '$username' has now been deleted.";
    html::new_paragraph();

    $this->browse_users();
  }
  #-----------------------------------------------------

  function display_user_roles( $username = NULL ) {

    echo 'User role(s): ';
    $statement = 'select role_name from ' . $this->db_users_and_roles_viewname()
               . " where username = '$username' order by role_name";
    $roles = $this->db_select_into_array( $statement );
    $i = 0;
    $role_count = count($roles);
    foreach( $roles as $row ) {
      $i++;
      $role_name = $row['role_name'];
      if( $role_name ) {
        if( $i > 1 ) html::bullet_point();
        echo ' ' . $role_name . ' ';
      }
      else
        echo 'None';
    }
  }
  #-----------------------------------------------------

  function remember_get_parms() {

    if( count( $_GET ) == 0 ) return;

    foreach( $_GET as $fieldname => $value ) {
      if( $fieldname == SESSION_TOKEN_FIELD ) continue;
      if( $fieldname == 'logout' ) continue;

      # If a record ID has been passed in, then go to that record after successful login

      # Field *name* must be a valid column name of the type we use in our database,
      # i.e. alphanumeric plus underscores
      if( ! $this->is_alphanumeric( $fieldname, $allow_underscores = TRUE )) 
        die( 'Invalid input.' );

      # Field *value* must be a valid key value for our database, i.e. integer, or possibly 
      # an HTML ID i.e. alphanumeric plus underscores, hyphens, colons, starting with a letter.
      if( ! $this->is_integer( $value ) && ! $this->is_html_id( $value ))
        die( 'Invalid input.' );

      html::hidden_field( GET_RECORD_AFTER_LOGIN_PREFIX . $fieldname, $value );
    }
  }
  #-----------------------------------------------------

  function carry_forward_get_parms() {

    # The parameters that may originally have been passed via GET have been written to hidden fields
    # and so will now be in POST.

    foreach( $_POST as $fieldname => $value ) {

      if( $this->string_starts_with( $fieldname, GET_RECORD_AFTER_LOGIN_PREFIX )) { 
        if( ! $this->is_alphanumeric( $fieldname, $allow_underscores = TRUE )) 
          die( 'Invalid input.' );

        # Re-read from POST using the proper validation procedure
        $value = $this->read_post_parm( $fieldname );

        html::hidden_field( $fieldname, $value );
      }
    }
  }
  #-----------------------------------------------------

  function reinstate_get_parms() {

    # The parameters that may originally have been passed via GET have been written to hidden fields
    # and so will now be in POST. Write them back into GET.

    foreach( $_POST as $fieldname => $value ) {

      if( $this->string_starts_with( $fieldname, GET_RECORD_AFTER_LOGIN_PREFIX )) { 
        if( ! $this->is_alphanumeric( $fieldname, $allow_underscores = TRUE )) 
          die( 'Invalid input.' );

        # Re-read from POST using the proper validation procedure
        $value = $this->read_post_parm( $fieldname );

        # Strip off the temporary prefix that we used to keep track of which parameters came from GET
        $fieldname = substr( $fieldname, strlen( GET_RECORD_AFTER_LOGIN_PREFIX ));

        # Put it back into GET
        $this->write_get_parm( $fieldname, $value );
      }
    }
  }
  #-----------------------------------------------------
}
?>
