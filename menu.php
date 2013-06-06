<?php
/*
 * PHP class for handling menus
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */



require_once 'dbentity.php';

class Menu extends DBEntity {

  #---------------------------------------------
  # Properties based on "CFG_PREFIX_menu" table
  #---------------------------------------------
  var $menu_item_id  ;
  var $menu_item_name;
  var $menu_order    ;
  var $parent_id     ;
  var $hidden_parent ;
  var $has_children  ;
  var $class_name    ;
  var $method_name   ;

  var $option_type;  # gets set to 'login', 'menu' or 'form'

  #------------------
  # Other properties
  #------------------
  var $menu_group;  # array holding list of options in submenu

  #-----------------------------------------------------

  function menu( &$db_connection, $item_id = NULL, $class_name = NULL, $method_name = NULL ) { 

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->DBEntity( $db_connection );

    #-------------------------------------
    # Get details of requested menu option
    #-------------------------------------
    $this->item_id = $item_id;
    $this->class_name = $class_name;
    $this->method_name = $method_name;

    if( $item_id == NULL && $class_name == NULL && $method_name == NULL ) {
      $option_exists = TRUE;
      $this->option_type = 'menu';
      $this->menu_item_name = 'Main Menu';
    }

    elseif( $item_id != NULL ) {
      $option_exists = $this->set_item_by_id();
      if( $this->has_children ) 
        $this->option_type = 'menu';
      else
        $this->option_type = 'form';
    }

    elseif( $this->class_name != NULL and $this->method_name != NULL ) {
      $option_exists = $this->set_item_by_class_and_method();
      $this->option_type = 'form';
    }

    if( ! $option_exists ) {
      if( $this->debug ) {
        $this->echo_safely( 'Menu item id: "' . $this->item_id . '"' );
        echo LINEBREAK;
        $this->echo_safely( 'Class name: "' . $this->class_name . '"' );
        echo LINEBREAK;
        $this->echo_safely( 'Method name: "' . $this->method_name . '"' );
        echo LINEBREAK;
      }
      die( 'Invalid menu option details.' );
    }
  }
  #-----------------------------------------------------

  function page_body_start() {

    #-------------------------------------------------
    # You may want to set focus on a particular field.
    #-------------------------------------------------
    $focus_form  = $this->read_post_parm( 'focus_form' );
    $focus_field = $this->read_post_parm( 'focus_field' );
    $required_anchor = $this->read_post_parm( 'required_anchor' );

    #------------------------------------------------------------------------------------------------------
    # If you want to set focus in a form called directly from the main menu, you need to hard-code the form
    # and field name here, as you will not have had a chance to set up hidden fields in an earlier form.
    # Prime example is "Search by barcode".
    #------------------------------------------------------------------------------------------------------
    if( $focus_form==NULL || $focus_field==NULL ) { 
      $focus_form = $this->class_name . '_' . $this->method_name; 
      $focus_field = NULL;

      if( $focus_form == 'barcode_search_by_barcode' ) $focus_field = 'barcode';
    }

    # The HTML class will only attempt to set focus if BOTH form and field are non-null
    html::body_start( $focus_form, $focus_field, $required_anchor );
  }
  #-----------------------------------------------------

  function page_body_end() {

    html::body_end();
  }
  #-----------------------------------------------------

  function run_menu( $username=NULL, $person_name=NULL, $login_time=NULL, $prev_login=NULL )
  {
    $this->help_obj = new Help( $this->db_connection );
    $this->help_obj->set_help_by_menu_item( $this->menu_item_id );

    $this->username = $username;
    $this->person_name = $person_name;
    $this->login_time = $login_time;
    $this->prev_login = $prev_login;

    $suppress_breadcrumbs = FALSE;
    $suppress_colours = FALSE;

    $printable_output = $this->read_post_parm( 'printable_output' );
    $csv_output = $this->read_post_parm( 'csv_output' );

    if( $printable_output ) {
      $suppress_breadcrumbs = TRUE;
      $suppress_colours = TRUE;
    }

    $menu_group_selected = FALSE;

    if( $this->option_type == 'menu' ) {  # if the menu has only one option, go straight into it
      $this->menu_group = NULL;
      $this->select_menu_group();
      if( count( $this->menu_group ) == 1 ) { # re-initialise menu object
        $row = $this->menu_group[0];
        $this->menu( $this->db_connection, $item_id = $row['menu_item_id'] );
        $this->run_menu(  $this->username,
                          $this->person_name,
                          $this->login_time,
                          $this->prev_login );
        return;
      }
      else
        $menu_group_selected = TRUE;
    }

    $this->construct_breadcrumb_trail();

    html::html_start();

    html::html_head_start();
    html::write_stylesheet();
    $this->write_js_check_form_valid();        # Call this in "on submit" event of any form (first call a function 
                                               # to set "validation err" field to 1 if applicable).
    $this->write_js_drop_form_validation();    # Can be called by Cancel button in forms with JS validation enabled
    $this->write_js_check_value_is_numeric();  # Function which may optionally be used for client-side validation.
    $this->write_js_open_search_window();      # Can open a search window which then returns a value to calling form

    html::html_head_end();

    $this->page_body_start();

    $this->page_head( $override_title = NULL, $suppress_breadcrumbs, $suppress_colours );

    #-------------------------------------
    #  Either display list of menu options
    #-------------------------------------
    if( $this->option_type == 'menu' ) {

      if( ! $menu_group_selected ) {
        $this->menu_group = NULL;
        $this->select_menu_group();
      }
      if( is_array( $this->menu_group )) $this->display_menu_form();
    }


    #--------------------------------------------------------------------------------------------
    # Or invoke a method (given EITHER in menu option info from DB, OR passed from previous form)
    #--------------------------------------------------------------------------------------------
    elseif( $this->class_name != NULL and $this->method_name != NULL ) {

      $class_to_create = trim( strtolower( $this->class_name ));
      $method_to_call  = trim( strtolower( $this->method_name ));

      $valid_classes = $this->app_get_declared_classes();
      $is_valid = array_search( $class_to_create, $valid_classes );
      if( $is_valid ) {
        $valid_methods = $this->app_get_class_methods( $class_to_create );
        $is_valid = array_search( $method_to_call, $valid_methods );
      }

      if( $is_valid ) {
        $this->write_session_parm( 'latest_menu', $this->parent_id );

        $menu_item = new $class_to_create( $this->db_connection );

        # Allow the called class to know which menu option has called it
        $menu_item->menu_item_id          = $this->menu_item_id;
        $menu_item->menu_item_name        = $this->menu_item_name ;
        $menu_item->menu_parent_id        = $this->parent_id ;
        $menu_item->menu_class_name       = $this->class_name ;
        $menu_item->menu_method_name      = $this->method_name ;
        $menu_item->menu_user_restriction = $this->user_restriction ;
        $menu_item->menu_hidden_parent    = $this->hidden_parent ;
        $menu_item->menu_called_as_popup  = $this->called_as_popup ;

        $menu_item->printable_output      = $printable_output;
        $menu_item->csv_output            = $csv_output;

        $menu_item->username              = $username;
        $menu_item->person_name           = $person_name;

        $menu_item->$method_to_call();

        if( $class_to_create == 'user' ) {  # User details may have changed. Refresh before displaying page footer.
          $menu_item->set_user( $username );
          $this->person_name = $menu_item->get_person_name();
        }
      }
      else
        die('Menu option still under construction.');
    }

    $this->page_foot( $suppress_breadcrumbs );
    $this->page_body_end();

    html::html_end();
  }
  #-----------------------------------------------------

  function display_menu_form() {

    if( is_array( $this->menu_group )) {
      $this->write_session_parm( 'latest_menu', $this->menu_item_id );

      html::div_start( 'class="mainmenu"');
      echo LINEBREAK;

      html::ulist_start();   # <ul>

      foreach( $this->menu_group as $row ) {
        if( is_array( $row )) {
          html::listitem_start();  # <li>

          $href = $_SERVER['PHP_SELF'] . '?menu_item_id=' . $row['menu_item_id'];
          $title = $row['menu_item_name'];

          html::link_start( $href, $title );  # <a>
          echo $title;
          html::link_end();                   # </a>

          html::listitem_end();  # </li>
          html::new_paragraph();
        }
      }

      html::ulist_end();   # </ul>

      echo LINEBREAK;
      html::div_end();

    }
  }
  #-----------------------------------------------------

  function set_item_by_id() {

    if( $this->item_id == NULL || $this->item_id < 1 ) return NULL;

    $statement = 'select * from ' . $this->db_menu_tablename() . " where menu_item_id = $this->item_id";
    $statement = $statement . ' and menu_item_id > 0'; # Do not let them display hidden options (parent id -1)
    $statement = $statement . $this->restrict_menu_access();

    $option_exists = $this->db_select_into_properties( $statement );

    $this->orig_parent_id = $this->parent_id;

    if( $option_exists ) {
      if( $this->parent_id < 0 ) {
        $option_exists = FALSE; # Do not normally let them display hidden options (parent id -1)
        # However, let them call a popup search window 
        # *IF* flag is set to say this menu item can be called in this way.
        if( $this->called_as_popup ) $option_exists = TRUE;
      }
    }

    return $option_exists;
  }
  #-----------------------------------------------------
  # N.B. Hidden options should be given a parent ID of -1

  function set_item_by_class_and_method() {

    if( $this->class_name == NULL || $this->method_name == NULL ) return NULL;

    $statement = 'select * from ' . $this->db_menu_tablename() . " where class_name = '$this->class_name' " 
                 . " and method_name='$this->method_name'";
    $statement = $statement . $this->restrict_menu_access();
    $statement = $statement . ' order by menu_item_name';

    # Some users may have access to several menus, with the same option on all the menus.
    # Try to work out where they are really coming from.
    $latest_menu = $this->read_session_parm( 'latest_menu' );
    $found_parent = FALSE;
    $options = $this->db_select_into_array( $statement );

    $option_exists = 0;
    $row = NULL;
    if( is_array( $options )) {
      $option_exists = count( $options );
      foreach( $options as $row ) {
        if( $row['parent_id'] == $latest_menu || $row['hidden_parent'] == $latest_menu ) {
          $found_parent = TRUE;
          break;
        }
      }
    }

    if( $option_exists && ! $found_parent ) {
      $row = $options[0];
    }
 
    if( $option_exists ) {
      foreach( $row as $colname => $colvalue ) {
        $this->$colname = $colvalue;
      }
    }

    $this->orig_parent_id = $this->parent_id;

    if( $this->parent_id < 0 ) $this->parent_id = $this->hidden_parent;

    return $option_exists;
  }
  #-----------------------------------------------------

  function select_menu_group() {

    $statement = 'select * from ' . $this->db_menu_tablename() . ' where parent_id ';
    if( $this->menu_item_id == NULL || $this->menu_item_id < 1 )
      $statement = $statement . ' is null';
    else
      $statement = $statement . " = $this->menu_item_id";
    $statement = $statement . $this->restrict_menu_access();
    $statement = $statement . ' order by menu_order';

    $this->menu_group = $this->db_select_into_array( $statement );
  }
  #-----------------------------------------------------

  function select_menu_item_by_id( $menu_item_id ) {

    if( $menu_item_id == NULL || $menu_item_id < 1 ) return NULL;

    $statement = 'select * from ' . $this->db_menu_tablename() . " where menu_item_id = $menu_item_id";
    $statement = $statement . $this->restrict_menu_access();

    $this->db_run_query( $statement );
    $row = $this->db_fetch_next_row();
    return $row;
  }
  #-----------------------------------------------------

  function breadcrumbs() {

    $breadcrumb_trail_printed = FALSE;
    if( ! is_array( $this->breadcrumb_trail )) return $breadcrumb_trail_printed;

    $option_depth = 0;
    foreach( $this->breadcrumb_trail as $option ) {
      $option_depth++;
      if( $option_depth > 1 ) html::bullet_point();

      if( ! $option ) { # main menu
        $href = $_SERVER['PHP_SELF'] . '?menu_item_id=';
        $parent_name = 'Main Menu';
      }
      else {
        $href = $_SERVER['PHP_SELF'] . '?menu_item_id=' . $option;
        $row = $this->select_menu_item_by_id( $option );
        $parent_name = $row['menu_item_name'];
      }

      html::link_start( $href, $parent_name );
      echo $parent_name;
      html::link_end();
    }

    $breadcrumb_trail_printed = TRUE;
    return $breadcrumb_trail_printed;
  }
  #-----------------------------------------------------

  function construct_breadcrumb_trail() {

    $this->breadcrumb_trail = array();

    if( ! $this->menu_item_id )  # in top-level, main menu
      $this->breadcrumb_trail = NULL;

    else {
      if( $this->parent_id < 0 && $this->hidden_parent > 0 )
        $this->parent_id = $this->hidden_parent;

      $parent_id = $this->parent_id;
      while( $parent_id > 0 ) { # Parent menu is not top menu
        $this->breadcrumb_trail[] = $parent_id;
        $row = $this->select_menu_item_by_id( $parent_id );
        $parent_id = $row[ 'parent_id' ];
        if( $parent_id <= 0 ) $parent_id = $row[ 'hidden_parent' ];
      }
      $this->breadcrumb_trail[] = $parent_id;  # add main menu
      krsort( $this->breadcrumb_trail );
    }
  }
  #-----------------------------------------------------

  function page_head( $override_title = NULL, $suppress_breadcrumbs = FALSE, $suppress_colours = FALSE ) {

    if( $this->called_as_popup ) $suppress_breadcrumbs = TRUE;

    if( $suppress_colours ) {
      $div_class = 'printbanner';
      $text_class = 'printbannertext';
      $logo_class = 'printbannerlogo';
    }
    else {
      $div_class = 'banner';
      $text_class = 'bannertext';
      $logo_class = 'bannerlogo';
    }
      
    html::div_start( ' class="' . $div_class . '" id="pagebanner" ' );

    html::page_top_anchor();
    echo LINEBREAK;

    echo  '<img src="ox_brand1_pos.gif" alt="University of Oxford logo" class="' . $logo_class . '">';

    html::h1_start();
    if( CONSTANT_DATABASE_NAME == CONSTANT_LIVE_DATABASE_NAME )
      echo CFG_SYSTEM_TITLE;
    else
      echo trim( CFG_SYSTEM_TITLE ) . ': Test/Training DB';
    html::h1_end();

    if( ! $suppress_breadcrumbs ) {
      $breadcrumb_trail_printed = $this->breadcrumbs();
      if( $breadcrumb_trail_printed ) html::bullet_point();

      $this->menu_extra_links_in_banner(); # provide a way for extra options to be added to the page banner 

      html::link_to_page_bottom( $tabindex=1, $title='Bottom of Page', $accesskey = 'Z' );

      html::bullet_point();
      $href = $_SERVER['PHP_SELF'] . '?logout=1';
      html::link_start( $href, 'Log out of ' . CFG_SYSTEM_TITLE );
      echo 'Logout';
      html::link_end();

      if( $this->class_name != 'help' ) { # don't go round and round in circles, getting help on help etc.
        $help_link = $this->help_obj->get_link_to_current_help_page();
        if( $help_link ) {
          html::bullet_point();
          echo $help_link;

          if( $this->help_obj->help_page_is_editable()) { # only available to supervisors
            $this->set_post_help_edit_parms();
            $this->help_obj->change_help_url_button( $this->post_help_edit_menu_item_id );
            $this->help_obj->help_edit_button( $this->post_help_edit_menu_item_id );
          }
        }
      }
    }

    html::h2_start();
    if( $override_title )
      echo $override_title;
    else
      echo $this->menu_item_name;
    html::h2_end();

    echo LINEBREAK;
    echo LINEBREAK;

    html::div_end();
    html::new_paragraph();
  }
  #-----------------------------------------------------------------

  function page_foot( $suppress_breadcrumbs = FALSE ) {

    if( $this->called_as_popup ) $suppress_breadcrumbs = TRUE;

    html::linebreak();
    html::horizontal_rule();
    html::new_paragraph(' class="footerlinks" ');
    if( $suppress_breadcrumbs ) return;

    $breadcrumb_trail_printed = $this->breadcrumbs();
    if( $breadcrumb_trail_printed ) html::bullet_point();

    html::link_to_page_top(  $tabindex=1, $title='Top of Page', $accesskey = 'P' );
    html::page_bottom_anchor();

    html::bullet_point();
    $href = $_SERVER['PHP_SELF'] . '?logout=1';
    html::link_start( $href, 'Log out of ' . CFG_SYSTEM_TITLE );
    echo 'Logout';
    html::link_end();

    if( $this->class_name != 'help' ) { # don't go round and round in circles, getting help on help etc.
      $help_link = $this->help_obj->get_link_to_current_help_page();
      if( $help_link ) {
        html::bullet_point();
        echo $help_link;
      }
    }
    html::new_paragraph();

    html::table_start( ' class="footnotes" width="100%" ' );
    html::tablerow_start();
    html::tabledata_start();

    $this->shortcut_key_notes();

    html::tabledata_end();
    html::tabledata_start( ' class="rightaligned" ' );

    if( $this->username ) {
      html::small_start();
      echo $this->get_datetime_now_in_words();
      echo LINEBREAK;
      if( $this->user_is_supervisor())
        echo 'YOU ARE LOGGED IN AS A SUPERVISOR:' ;
      else
        echo 'You are logged in as: ' ;
      echo LINEBREAK;
      echo $this->person_name . ' (' . $this->username . ')' ;
      echo LINEBREAK;
      echo 'Latest login: ' . $this->postgres_date_to_dd_mm_yyyy( $this->login_time );
      echo LINEBREAK;
      echo 'Previous login: ' . $this->postgres_date_to_dd_mm_yyyy( $this->prev_login );
      html::small_end();
    }

    html::tabledata_end();
    html::tablerow_end();
    html::table_end();

    echo LINEBREAK;
    html::italic_start();
    $this->aeolus_version_info();
    html::italic_end();
  }
  #-----------------------------------------------------------------

  function shortcut_key_notes() {
    html::small_start();
    echo 'Windows shortcut keys:'; 
    html::ulist_start();
    html::listitem( 'In Internet Explorer press Alt and shortcut key. ' );
    html::listitem( 'In Mozilla Firefox press Shift and Alt and shortcut key.' );
    html::listitem( 'You may then need to press Return.' );
    html::ulist_end();
    html::small_end();
  }
  #-----------------------------------------------------------------

  function restrict_menu_access() { 

    $this->user_roles    = $this->read_session_parm( 'user_roles' );

    $where_clause = " and (user_restriction = '' ";
    if( $this->user_roles ) $where_clause = $where_clause . " or user_restriction in ( $this->user_roles )";
    $where_clause = $where_clause . ')';
    return $where_clause;
  }
  #-----------------------------------------------------------------

  function return_to_main_menu() {  # called when a class and method has previously been called, but user cancelled

    $this->clear();
    $this->select_menu_group();
    if( is_array( $this->menu_group )) $this->display_menu_form();
  }
  #-----------------------------------------------------------------

  function get_menu_item_id() {
    return $this->menu_item_id;
  }
  #-----------------------------------------------------------------

  function get_menu_item_name() {
    return $this->menu_item_name;
  }
  #-----------------------------------------------------------------

  function get_menu_item_class() {
    return $this->class_name;
  }
  #-----------------------------------------------------------------

  function get_menu_item_method() {
    return $this->method_name;
  }
  #-----------------------------------------------------------------

  function set_post_help_edit_parms() {

    $this->post_help_edit_menu_item_id = NULL;

    if( $this->orig_parent_id < 0 )                # this option is NOT directly on the menu, 
                                                   # return to nearest calling menu
      $this->post_help_edit_menu_item_id = $this->hidden_parent;

    else # this option is directly on the menu
      $this->post_help_edit_menu_item_id = $this->menu_item_id;
  }
  #-----------------------------------------------------------------
  function menu_extra_links_in_banner() { # provide a way for extra options to be added to the page banner 

    if( $this->get_system_prefix() == 'pmdb' ) {
      if( class_exists( 'PMDB_Entity' )) {
        PMDB_Entity::pmdb_extra_links_in_banner();
      }
    }
  }
  #-----------------------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'focus_form':
      case 'focus_field':
      case 'required_anchor':

        return $this->is_alphanumeric_or_blank( $this->parm_value, $allow_underscores = TRUE );

      case 'latest_menu':
        return $this->is_integer( $this->parm_value, $allow_negative = TRUE );

      case 'breadcrumb_trail':
        if( $this->parm_value == NULL )
          return TRUE;
        else
          return $this->is_array_of_integers( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
