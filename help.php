<?php
/*
 * PHP class for handling help pages
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */


require_once 'dbentity.php';

class Help extends DBEntity {

  #-----------------------------------------------------
  # Constructor

  function Help( &$db_connection ) { 

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->DBEntity( $db_connection );

  }
  #-----------------------------------------------------

  function set_help( $page_id = NULL, $order_in_manual = NULL ) {

    $this->clear();
    if( ! $page_id ) return NULL;
    if( $page_id == 'index' ) return NULL;

    $statement = 'select * from ' . $this->db_help_pages_tablename() . ' where page_id = ' . $page_id;
    $this->db_select_into_properties( $statement );

    $this->order_in_manual = $order_in_manual;

    $this->construct_default_url();
    $this->set_url();

    # Prefix page title with 'Help: ' if it does not already start with this.
    if( substr( $this->page_title, 0, strlen( 'Help:' )) != 'Help:' )
      $this->page_title = 'Help: ' . $this->page_title;

    $this->post_help_edit_menu_item_id = $this->read_post_parm( 'post_help_edit_menu_item_id' );

    return $this->full_url;
  }
  #-----------------------------------------------------

  function set_help_by_menu_item( $menu_item_id = NULL ) {  # returns link to this help page

    $this->clear();

    $statement = 'select * from ' . $this->db_help_options_tablename() . ' where menu_item_id ';
    if( $menu_item_id )
      $statement .= ' = ' . $menu_item_id;
    else
      $statement .= ' is null';  # Main menu has null menu item ID.
    $statement .= ' order by button_name desc';  # get button-specific pages first, then general ones
    $options = $this->db_select_into_array( $statement );

    if( ! is_array( $options )) return NULL;

    foreach( $options as $row ) {
      extract( $row, EXTR_OVERWRITE );
      if( $button_name ) {  # there is a help page to cover the situation after a specific button was pressed
        if( $this->parm_found_in_post( $button_name )) {
          $this->set_help( $help_page_id, $order_in_manual );
          break;
        }
      }
      else # no button name, so we must be on the last possible option
        $this->set_help( $help_page_id, $order_in_manual );
    }

    return $this->short_url;
  }
  #-----------------------------------------------------

  function construct_default_url() {  # assumes help page info has been selected into properties

    # E.g. CFG_PREFIXhelp.php?main_menu (In document root. Prefixed by dev_ or test_ if applicable.)

    # If running manual production procedure from command line, server name etc will be wrong
    # so we'll need to manually set them as environment variables.
    if( $this->producing_manual_from_commandline_script()) {
      $server_name_for_help = getenv( 'AEOLUS_MANUAL_SERVERNAME' ); # 'sers018.sers.ox.ac.uk'
      $php_self_for_help    = getenv( 'AEOLUS_MANUAL_PHP_SELF' );   # normally '/CFG_PREFIX/CFG_PREFIXhelp.php'
    }
    else {
      $php_self_for_help = $_SERVER[ 'PHP_SELF' ];
      $server_name_for_help = $_SERVER[ 'SERVER_NAME' ];
    }

    $last_slash_pos = strrpos( $php_self_for_help, '/' );
    if( ! $last_slash_pos ) return;
    $subdir = substr( $php_self_for_help, 0, $last_slash_pos );

    $this->default_short_url = '';
    if( CONSTANT_DATABASE_TYPE != 'live' ) $this->default_short_url = CONSTANT_DATABASE_TYPE . '_';

    $this->default_short_url .= $this->get_system_prefix() . 'help.php'
               .  '?'
               .  'page_id=' . $this->page_id;

    if( $this->order_in_manual ) $this->default_short_url .= '&order_in_manual=' . $this->order_in_manual;

    $this->default_full_url = 'http://' . $server_name_for_help . $subdir . '/' . $this->default_short_url;

    $this->index_url = str_replace( 'page_id=' . $this->page_id, 'page_id=index', $this->default_full_url );
    $this->index_url = str_replace( '&order_in_manual=' . $this->order_in_manual, '', $this->index_url );

    $this->printable_index_url = $this->index_url . '&printable_output=Y';

    $this->whole_manual_url = str_replace( 'page_id=index', 'page_id=manual', $this->index_url );
  }
  #-----------------------------------------------------

  function set_url() {  # assumes help page info has been selected into properties

    if( $this->custom_url ) {
      $this->short_url = $this->custom_url;
      $this->full_url = $this->custom_url;
    }
    else {
      $this->short_url = $this->default_short_url;
      $this->full_url = $this->default_full_url;
    }
  }
  #-----------------------------------------------------

  function help_page_is_custom_url() {  # assumes help page info has been selected into properties

    if( $this->custom_url )
      return TRUE;
    else
      return FALSE;
  }
  #-----------------------------------------------------

  function help_page_is_editable() {  # assumes help page info has been selected into properties

    if( $this->full_url && $this->user_is_supervisor())
      return TRUE;
    else
      return FALSE;
  }
  #-----------------------------------------------------

  function get_link_to_current_help_page( $target = '_blank' ) {

    if( ! $this->short_url )
      return NULL;

    else {
      return html::return_link( $this->short_url, 'Help', $title = $this->page_title, $target );
    }
  }
  #-----------------------------------------------------

  function get_link_to_default_help_page( $target = '_blank' ) {

    if( ! $this->default_full_url )
      return NULL;

    else {
      return html::return_link( $this->default_full_url, $this->default_full_url, $this->page_title, $target );
    }
  }
  #-----------------------------------------------------

  function get_link_to_current_help_by_full_url( $target = '_blank' ) {

    if( ! $this->full_url )
      return NULL;

    else {
      return html::return_link( $this->full_url, $this->full_url, $this->page_title, $target );
    }
  }
  #-----------------------------------------------------

  function get_link_to_current_help_by_page_title( $target = '_blank' ) {

    if( ! $this->full_url )
      return NULL;

    else {
      return html::return_link( $this->full_url, $this->page_title, $this->page_title, $target );
    }
  }
  #-----------------------------------------------------

  function get_link_to_printable_version( $target = '_blank' ) {

    if( ! $this->full_url )
      return NULL;

    else {
      return html::return_link( $this->full_url . '&printable_output=Y', 
                                'Printable version', 'Printable version of this page', $target );
    }
  }
  #-----------------------------------------------------

  function get_link_to_index_url( $target = '_self' ) {

    if( ! $this->index_url )
      return NULL;

    else {
      return html::return_link( $this->index_url, 'Help index', $title = 'Help index', $target );
    }
  }
  #-----------------------------------------------------

  function get_link_to_printable_index_url( $target = '_self' ) {

    if( ! $this->printable_index_url )
      return NULL;

    else {
      return html::return_link( $this->printable_index_url, 'Printable index', 
                                $title = 'Printable version of help index', $target );
    }
  }
  #-----------------------------------------------------

  function get_link_to_whole_manual_url( $target = '_blank' ) {

    if( ! $this->whole_manual_url )
      return NULL;

    else {
      return html::return_link( $this->whole_manual_url, 'Whole manual', 
                                $title = 'Printable version of whole manual', $target );
    }
  }
  #-----------------------------------------------------
  # Only for supervisors

  function help_edit_button( $post_help_edit_menu_item_id, $called_from_help_summary=FALSE ) {  

    if( $this->help_page_is_editable()) {
      html::form_start( 'help', 'edit_help' );

      html::hidden_field( 'page_id', $this->page_id );

      html::hidden_field( 'post_help_edit_menu_item_id', $post_help_edit_menu_item_id );

      if( $this->help_page_is_custom_url()) {
        echo '*** N.B. Using custom URL. ***';
        $button_title = 'Edit Default Help';
      }
      else
        $button_title = 'Edit Help';

      if( ! $called_from_help_summary ) $button_title = '[ ' . $button_title . ' ]';

      html::submit_button( 'edit_help_button', $button_title );
      html::form_end();
    }
  }
  #-----------------------------------------------------
  # Only for supervisors

  function change_help_url_button( $post_help_edit_menu_item_id, $called_from_help_summary=FALSE ) {  

    if( $this->help_page_is_editable()) {
      html::form_start( 'help', 'change_help_url' );

      html::hidden_field( 'page_id', $this->page_id );

      html::hidden_field( 'post_help_edit_menu_item_id', $post_help_edit_menu_item_id );

      $button_title = 'Change Help URL';
      if( ! $called_from_help_summary ) $button_title = '[ ' . $button_title . ' ]';

      html::submit_button( 'change_help_url_button', $button_title );
      html::form_end();
    }
  }
  #-----------------------------------------------------

  function edit_help( $page_id = NULL ) {

    if( ! $page_id ) $page_id = $this->read_post_parm( 'page_id' );
    if( ! $page_id ) $this->die_on_error( 'Invalid page ID.' );

    $this->set_help( $page_id );
    if( ! $this->page_id ) $this->die_on_error( 'Invalid page ID.' );

    html::h3_start();
    echo $this->page_title;
    html::h3_end();

    if( $this->help_page_is_custom_url()) {
      html::div_start( 'class="warning"' );
      echo 'Warning: you are currently using a custom URL not the default one.';
      html::new_paragraph();
      echo 'You can edit the default help text, but the Help link in the page banner will not point at it.';

      html::new_paragraph();
      echo "To re-activate the default URL, go back and select 'Change Help URL'.";

      html::div_end( "warning" );
    }

    html::ulist_start();
    html::listitem( 'Please enter your text in the box below.' );
    html::listitem( "'Save draft' saves your changes in the database but does not publish the edited text." );
    html::listitem( "'Publish' makes the edited text visible on this URL: " 
                    . $this->get_link_to_default_help_page());
    html::ulist_end();

    html::form_start( 'help', 'save_help' );

    html::hidden_field( 'page_id', $this->page_id );

    html::hidden_field( 'post_help_edit_menu_item_id', $this->post_help_edit_menu_item_id );

    $this->buttons_for_saving_help();
    html::new_paragraph();

    html::textarea_start( 'draft_text', $rows = HELP_EDIT_ROWS, $columns = HELP_EDIT_COLS );
    $this->echo_safely( $this->draft_text );
    html::textarea_end();

    html::new_paragraph();
    $this->buttons_for_saving_help();

    html::form_end();
  }
  #-----------------------------------------------------

  function buttons_for_saving_help() {

    html::submit_button( 'save_button', 'Save draft' );
    echo ' (Shortcut key S) ';

    html::submit_button( 'publish_button', 'Publish' );
    echo ' ';

    html::submit_button( 'cancel_button', 'Cancel' );
  }
  #-----------------------------------------------------

  function save_help( $page_id = NULL ) {

    if( ! $page_id ) $page_id = $this->read_post_parm( 'page_id' );
    if( ! $page_id ) $this->die_on_error( 'Invalid page ID.' );

    $this->set_help( $page_id );
    if( ! $this->page_id ) $this->die_on_error( 'Invalid page ID.' );

    html::h3_start();
    echo $this->page_title;
    html::h3_end();

    if( $this->parm_found_in_post( 'cancel_button' )) {
      echo 'Changes to help text were cancelled.';
      html::new_paragraph();
      $this->post_help_edit_options();
      return;
    }

    $this->draft_text = $this->read_post_parm( 'draft_text' );

    $statement = 'update ' . $this->db_help_pages_tablename() 
               . " set draft_text = '" . $this->escape( $this->draft_text ) . "' "
               . " where page_id = $page_id";
    $this->db_run_query( $statement );

    echo $this->get_datetime_now_in_words() . ': Changes to help text were saved in the database.';
    html::new_paragraph();

    if( $this->parm_found_in_post( 'publish_button' )) {
      $this->publish_help( $page_id );
    }
    else {  # continue editing or return to calling menu option
      $this->post_help_edit_options();
      return;
    }
  }
  #-----------------------------------------------------

  function publish_help( $page_id = NULL ) {

    if( ! $page_id ) $page_id = $this->read_post_parm( 'page_id' );
    if( ! $page_id ) $this->die_on_error( 'Invalid page ID.' );

    $this->set_help( $page_id ); # picks up recently-saved help text
    if( ! $this->page_id ) $this->die_on_error( 'Invalid page ID.' );

    if( $this->parm_found_in_post( 'cancel_button' )) {
      echo 'Changes to help text were cancelled.';
      html::new_paragraph();
      $this->post_help_edit_options();
      return;  # later change this to a return to the appropriate screen
    }

    $statement = 'update ' . $this->db_help_pages_tablename()
               . " set published_text = '" . $this->escape( $this->draft_text ) . "' "
               . " where page_id = $page_id";
    $this->db_run_query( $statement );

    echo $this->get_datetime_now_in_words() . ': Saved text has now been published on ' 
         . $this->get_link_to_default_help_page();
    html::new_paragraph();

    $this->post_help_edit_options();
    return;
  }
  #-----------------------------------------------------

  function post_help_edit_options() {

    html::div_start( 'class="highlight1"' );
    html::form_start( 'help', 'edit_help' );
    html::hidden_field( 'page_id', $this->page_id );
    html::hidden_field( 'post_help_edit_menu_item_id', $this->post_help_edit_menu_item_id );
    echo 'Continue editing help text: ';
    html::submit_button();
    html::form_end();
    html::div_end( "highlight1" );

    html::new_paragraph();

    html::div_start( 'class="highlight2"' );
    $this->return_to_calling_menu_option_form();
    html::div_end( "highlight2" );
  }
  #-----------------------------------------------------

  function return_to_calling_menu_option_form() {

    if( ! $this->post_help_edit_menu_item_id )
      $post_help_edit_menu_item_name = 'Main menu';
    else {
      $statement = 'select menu_item_name, class_name, method_name from ' . $this->db_menu_tablename()
                 . " where menu_item_id = $this->post_help_edit_menu_item_id";
      $this->db_run_query( $statement );
      if ( $this->db_fetch_next_row()) {
        $post_help_edit_menu_item_name = $this->db_fetch_next_col();
        $post_help_edit_class_name     = $this->db_fetch_next_col();
        $post_help_edit_method_name    = $this->db_fetch_next_col();
      }
    }

    if( $post_help_edit_class_name == 'help' && $post_help_edit_method_name == 'help_pages_summary' ) {
      html::form_start( $post_help_edit_class_name, $post_help_edit_method_name );
      html::hidden_field( 'required_anchor', $this->get_anchor_name());
    }
    else {
      html::form_start( NULL, NULL, NULL, '_self',  FALSE, NULL, 'GET' );
      html::hidden_field( 'menu_item_id', $this->post_help_edit_menu_item_id );
    }

    echo "Return to $post_help_edit_menu_item_name: ";
    html::submit_button();
    html::form_end();
  }
  #-----------------------------------------------------

  function help_navigation_links( $page_top = FALSE ) {

    if( $this->printable_output ) {
      if( ! $page_top ) {
        html::italic_start();
        echo 'End of manual page ' . $this->order_in_manual . ' [ID ' . $this->page_id . ']. ' . $this->page_title;
        html::italic_end();
      }
    }
    else {  # not the printable version, a normal online page
      echo $this->get_link_to_printable_version( '_self' );
      html::bullet_point();

      echo $this->get_link_to_index_url();

      html::bullet_point();
      echo $this->get_link_to_whole_manual_url();

      if( $this->order_in_manual ) {
        $other_help = New Help( $this->db_connection );

        $statement = 'select min(order_in_manual) from ' . $this->db_help_options_tablename() 
                   . ' where order_in_manual > 0 '
                   . " and order_in_manual > $this->order_in_manual";
        $next_order = $this->db_select_one_value( $statement );

        $statement = 'select max(order_in_manual) from ' . $this->db_help_options_tablename() 
                   . ' where order_in_manual > 0 '
                   . " and order_in_manual < $this->order_in_manual";
        $prev_order = $this->db_select_one_value( $statement );

        if( $next_order ) {
          $statement = 'select help_page_id from ' . $this->db_help_options_tablename() 
                     . " where order_in_manual = $next_order";
          $next_page = $this->db_select_one_value( $statement );
          $other_help->set_help( $next_page, $next_order );
          html::bullet_point();
          $next_link = $other_help->get_link_to_current_help_by_page_title( $target = '_self' );
          $next_link = str_replace( 'Help:', 'Next help page:', $next_link );
          echo $next_link;
        }

        if( $prev_order ) {
          $statement = 'select help_page_id from ' . $this->db_help_options_tablename()
                     . " where order_in_manual = $prev_order";
          $prev_page = $this->db_select_one_value( $statement );
          $other_help->set_help( $prev_page, $prev_order );
          html::bullet_point();
          $prev_link = $other_help->get_link_to_current_help_by_page_title( $target = '_self' );
          $prev_link = str_replace( 'Help:', 'Previous help page:', $prev_link );
          echo $prev_link;
        }
      }
      if( $page_top ) html::horizontal_rule();
      html::new_paragraph();
    }

  }
  #-----------------------------------------------------

  function display_help( $producing_manual_as_one_file = FALSE ) {

    $page_id = $this->read_get_parm( 'page_id' );
    if( $page_id == 'index' ) {
      $this->display_help_index();
      return;
    }
    elseif( $page_id == 'manual' ) {
      $this->produce_manual();
      return;
    }

    if( ! $page_id ) $this->die_on_error( 'No help available.' );

    $order_in_manual = $this->read_get_parm( 'order_in_manual' );

    $this->set_help( $page_id, $order_in_manual );

    if( ! $this->published_text )
      echo 'No help available.';
    else {
      if( $this->contains_scripting( $this->published_text )) 
        $this->die_on_error( 'No help available.' );

      $suppress_colours = FALSE;
      $this->printable_output = $this->read_get_parm( 'printable_output' );
      if( $this->printable_output == 'Y' ) $suppress_colours = TRUE;

      if( ! $producing_manual_as_one_file ) {
        $menu_obj = new Menu( $this->db_connection );
        $menu_obj->debug = FALSE;
        $menu_obj->page_head( $override_title = $this->page_title, $suppress_breadcrumbs=TRUE, $suppress_colours );
        echo LINEBREAK;
        $this->help_navigation_links( TRUE );
      }


      echo nl2br( $this->safe_output( $this->published_text ));  # do not allow HTML tags etc, too insecure

      html::new_paragraph();
      $this->help_navigation_links();

      if( ! $this->printable_output ) {
        $show_link_to_page_top = FALSE;
        $one_pagelength_estimate = HELP_VIEW_ROWS_ESTIMATE * HELP_VIEW_COLS_ESTIMATE;

        if( strlen( $this->published_text ) > $one_pagelength_estimate )
          $show_link_to_page_top = TRUE;
        elseif( substr_count( $this->published_text, NEWLINE ) > HELP_VIEW_ROWS_ESTIMATE )
          $show_link_to_page_top = TRUE;

        if( $show_link_to_page_top ) {
          html::link_to_page_top(  $tabindex=1, $title='Back to top of page' );
          html::new_paragraph();
        }
      }

      html::horizontal_rule();

      if( ! $producing_manual_as_one_file ) {
        html::italic_start();
        $this->aeolus_version_info();
        html::italic_end();
      }
    }
  }
  #-----------------------------------------------------

  function change_help_url( $page_id = NULL ) {

    if( ! $page_id ) $page_id = $this->read_post_parm( 'page_id' );
    if( ! $page_id ) $this->die_on_error( 'Invalid page ID.' );

    $this->set_help( $page_id );
    if( ! $this->page_id ) $this->die_on_error( 'Invalid page ID.' );

    html::h3_start();
    echo $this->page_title;
    html::h3_end();

    html::new_paragraph();

    html::form_start( 'help', 'save_changed_help_url' );

    html::hidden_field( 'page_id', $this->page_id );
    html::hidden_field( 'post_help_edit_menu_item_id', $this->post_help_edit_menu_item_id );

    if( ! $this->custom_url ) html::div_start( 'class="highlight2"' );
    html::bold_start();
    echo 'By default, the Help link for this menu option will point to the following URL:';
    html::bold_end();
    html::new_paragraph();
    echo $this->get_link_to_default_help_page();
    if( ! $this->custom_url ) html::div_end( "highlight2" );

    html::new_paragraph();

    html::bold_start();
    echo 'You may wish to use a different URL to provide help for this menu option, e.g. a page within the OULS CMS.';
    html::bold_end();
    html::new_paragraph();
    echo 'If so, enter the alternative URL below.';
    html::new_paragraph();

    if( $this->custom_url ) html::div_start( 'class="highlight1 bold"' );
    html::input_field( 'custom_url', 'Alternative URL', $this->custom_url, FALSE, $size = 100 );
    if( $this->custom_url ) html::div_end( "highlight1 bold" );
    html::new_paragraph();

    html::italic_start();
    echo 'If you have entered an alternative URL and then wish to revert to the default, simply blank out '
         . ' the alternative URL and click Save. Any help text within the original default help page will '
         . ' have been preserved, and will become visible to the users again.';
    html::italic_end();

    html::new_paragraph();
    html::submit_button( 'save_button', 'Save' );
    html::submit_button( 'cancel_button', 'Cancel' );

    html::form_end();
  }
  #-----------------------------------------------------

  function save_changed_help_url( $page_id = NULL ) {

    if( ! $page_id ) $page_id = $this->read_post_parm( 'page_id' );
    if( ! $page_id ) $this->die_on_error( 'Invalid page ID.' );

    $this->set_help( $page_id );
    if( ! $this->page_id ) $this->die_on_error( 'Invalid page ID.' );

    html::h3_start();
    echo $this->page_title;
    html::h3_end();

    if( $this->parm_found_in_post( 'cancel_button' )) {
      html::italic_start();
      echo 'Any changes to help URL have been cancelled.';
      html::italic_end();
    }
    else {
      $this->custom_url = $this->read_post_parm( 'custom_url' );
      $this->custom_url = trim( $this->custom_url );
      $statement = 'update ' . $this->db_help_pages_tablename() 
                 . " set custom_url = '" . $this->escape( $this->custom_url ) . "' "
                 . " where page_id = $this->page_id";
      $this->db_run_query( $statement );
      html::italic_start();
      echo 'Changes to help URL have been saved.';
      html::italic_end();
      $this->set_help( $this->page_id );  # refresh all URL settings
    }

    html::new_paragraph();
    html::div_start( 'class="highlight2"' );
    echo 'Current URL type: ';
    
    if( $this->help_page_is_custom_url()) {
      html::bold_start();
      echo 'Custom';
      html::bold_end();
    }
    else
      echo 'Default';

    html::new_paragraph();
    echo 'Currently active URL is: ';
    html::new_paragraph();

    echo $this->get_link_to_current_help_by_full_url();
    html::div_end( "highlight2" );

    html::new_paragraph();
    $this->return_to_calling_menu_option_form();
  }
  #-----------------------------------------------------

  function help_pages_summary() {

    $statement = 'select page_id, page_title, custom_url from ' . $this->db_help_pages_tablename()
               . ' order by page_title, page_id';
    $pages = $this->db_select_into_array( $statement );

    if( count( $pages ) < 1 || ! is_array( $pages ))
      echo 'No help page details found.';
    else {
      foreach( $pages as $row ) {
        foreach( $row as $colname => $colvalue ) {
          $this->$colname = $colvalue;
        }
        $this->construct_default_url();
        $this->set_url();

        html::anchor( $this->get_anchor_name() );

        html::bold_start();
        echo $this->page_title;
        html::bold_end();
        html::new_paragraph();


        if( $this->help_page_is_custom_url()) {
          html::div_start( 'class="highlight2 bold"' );
          echo 'Current URL: '. $this->get_link_to_current_help_by_full_url();
          html::div_end( "highlight2 bold" );
          html::new_paragraph();
          html::italic_start();
          echo 'Not using default URL: ' . $this->get_link_to_default_help_page();
          html::italic_end();
          html::new_paragraph();
        }
        else {
          echo 'URL: '. $this->get_link_to_current_help_by_full_url();
        }

        html::new_paragraph();
        html::div_start('class="buttonrow"');

        $this->help_edit_button( $post_help_edit_menu_item_id = $this->menu_item_id, 
                                 $called_from_help_summary = TRUE );

        $this->change_help_url_button( $post_help_edit_menu_item_id = $this->menu_item_id, 
                                       $called_from_help_summary = TRUE );
        echo LINEBREAK;
        html::div_end('buttonrow');
        html::new_paragraph();

        html::horizontal_rule();
        html::new_paragraph();
      }
    }
  }
  #-----------------------------------------------------

  function get_anchor_name() {

    return 'page_id_' . $this->page_id;
  }
  #-----------------------------------------------------

  function write_help_to_script( $filename = NULL ) {

    if( ! $filename ) $filename = 'update_' . $this->get_system_prefix() . '_help.sql';

    $statement = 'select * from ' . $this->db_help_pages_tablename() . ' order by page_id';

    $pages = $this->db_select_into_array( $statement );
    if( ! $pages ) die( 'No data found' . NEWLINE );

    $handle = fopen( $filename, 'w' );
    if( ! $handle ) die( 'Unable to open file ' . $filename . ' for writing.' . NEWLINE );

    $comment = '-- ' . PERCENT_SIGN . 'P' 
                     . PERCENT_SIGN . ' ' 
                     . PERCENT_SIGN . 'I' 
                     . PERCENT_SIGN . ' 20' 
                     . PERCENT_SIGN . 'E' 
                     . PERCENT_SIGN . ' ' 
                     . PERCENT_SIGN . 'U' 
                     . PERCENT_SIGN . ' ' . NEWLINE ;
    fwrite( $handle, $comment );

    $comment = '-- Generated from ' . CONSTANT_DATABASE_TYPE . ' ' . $this->get_datetime_now_in_words() . NEWLINE
             . '-- by write_help_to_script.php and help.php: ' . NEWLINE ;
    fwrite( $handle, $comment );

    $comment = '-- php -q write_help_to_script.php ' . NEWLINE . NEWLINE;
    fwrite( $handle, $comment );

    foreach( $pages as $row ) {
      extract( $row, EXTR_OVERWRITE );
      if( ! $draft_text ) continue; # no need to write out an update statement unless there is some help entered.

      $statement = 'update ' . $this->db_help_pages_tablename() . ' set ' . NEWLINE;

      $statement .= "  -- Page title: $page_title " . NEWLINE;

      $statement .= "  published_text = '" . $this->escape( $published_text )  . "', "   . NEWLINE;

      if( $draft_text != $published_text )
        $statement .= "  draft_text = '" . $this->escape( $draft_text )      . "', "   . NEWLINE;

      if( $custom_url )
        $statement .= "  custom_url = '" . $this->escape( $custom_url )  . "' "   . NEWLINE;
      else
        $statement .= "  custom_url = null "   . NEWLINE;

      $statement .= "where page_id = $page_id ;"   . NEWLINE;

      if( $draft_text == $published_text ) {
        $statement .= "-- Draft text is same as published text for '$page_title'" . NEWLINE;
        $statement .= 'update ' . $this->db_help_pages_tablename() . ' set draft_text = published_text ';
        $statement .= "where page_id = $page_id ;"   . NEWLINE;
      }

      $statement .= NEWLINE . NEWLINE;
      fwrite( $handle, $statement );
    }

    $closed = fclose( $handle );
    if( ! $closed ) die( 'Unable to close file ' . $filename . NEWLINE );
  }
  #-----------------------------------------------------

  function insert_initial_help() {

    $statement = 'delete from ' . $this->db_help_options_tablename();
    $this->db_run_query( $statement );

    $statement = 'delete from ' . $this->db_help_pages_tablename();
    $this->db_run_query( $statement );


    #------------------------------------------------------------
    #- Initially have one help PAGE for each *TITLE* in the menu
    #------------------------------------------------------------
    $statement = 'insert into ' . $this->db_help_pages_tablename() . " (page_title) values ('Main menu')";
    $this->db_run_query( $statement );

    # Get the titles in roughly the order that they appear in the menu
    $statement = 'select menu_item_name, min( menu_item_id )from ' . $this->db_menu_tablename()
               . ' where menu_item_id >= 0 and menu_item_id not in ' 
               # No need to do a help page for each report
               . ' (select menu_item_id from ' . $this->db_reports_tablename() . ') ' 
               . " and coalesce(class_name,'') != 'help' "  # We don't want to go round and round in circles,
                                                            # editing the help on how to edit help!
               . ' group by menu_item_name '
               . ' order by min( menu_item_id )';
    $titles = $this->db_select_into_array( $statement );
    foreach( $titles as $row ) {
      extract( $row, EXTR_OVERWRITE );
      $statement = 'insert into ' . $this->db_help_pages_tablename() 
                 . " (page_title) values ('" . $this->escape( $menu_item_name ) . "')";
      echo "Inserting page: $menu_item_name" . NEWLINE;
      $this->db_run_query( $statement );
    }


    #----------------------------------------------------------
    #- Initially have one help OPTION for each *ID* in the menu
    #----------------------------------------------------------
    $statement = 'insert into ' . $this->db_help_options_tablename() . ' (menu_item_id, help_page_id)'
               . ' select null, min(page_id) from ' . $this->db_help_pages_tablename();
    echo 'Inserting help option for main menu' . NEWLINE;
    $this->db_run_query( $statement );


    $statement = 'insert into ' . $this->db_help_options_tablename() . ' (menu_item_id, help_page_id) '
               . ' select m.menu_item_id, p.page_id '
               . ' from ' . $this->db_menu_tablename() . ' m, ' . $this->db_help_pages_tablename() . ' p '
               . ' where m.menu_item_name = p.page_title';
    echo 'Inserting help options for other menu options' . NEWLINE;
    $this->db_run_query( $statement );

    $this->customise_initial_help();

    echo 'Finished creating initial help.' . NEWLINE;
  }
  #-----------------------------------------------------
  function customise_initial_help() {  # This method exists just to be overridden by child classes
    echo 'In main Help object: no customisation of initial help will be done.' . NEWLINE;
  }
  #-----------------------------------------------------

  function set_order_in_manual_by_user_restriction( $user_restriction, $page_id, $menu_depth = 0 ) {

    $this->order_in_manual = $this->order_in_manual + 1;

    $statement = 'update ' . $this->db_help_options_tablename() 
               . ' set order_in_manual = ' . $this->order_in_manual . ', '
               . " menu_depth = $menu_depth "
               . ' where menu_item_id in ( select menu_item_id from ' . $this->db_menu_tablename() 
               . " where user_restriction = '$user_restriction' ) and help_page_id = $page_id";
    $this->db_run_query( $statement );
  }
  #-----------------------------------------------------

  function get_manual_list() {

    $statement = 'select distinct o.order_in_manual, o.menu_depth, p.page_id, p.page_title, p.custom_url '
               . ' from ' . $this->db_help_options_tablename() . ' o, ' . $this->db_help_pages_tablename() . ' p '
               . ' where o.help_page_id = p.page_id '
               . ' and o.order_in_manual > 0 '
               . ' order by order_in_manual, page_id';
    $index = $this->db_select_into_array( $statement );
    return $index;
  }
  #-----------------------------------------------------

  function display_help_index( $producing_manual_as_one_file = FALSE ) {

    $suppress_colours = FALSE;
    $this->printable_output = $this->read_get_parm( 'printable_output' );
    if( $this->printable_output == 'Y' ) $suppress_colours = TRUE;

    $menu_obj = new Menu( $this->db_connection );
    $menu_obj->debug = FALSE;
    $override_title = 'Help Index';
    if( $producing_manual_as_one_file ) $override_title = 'User manual';
    $menu_obj->page_head( $override_title, $suppress_breadcrumbs=TRUE, $suppress_colours );

    if( $producing_manual_as_one_file ) {
     html::horizontal_rule();
     html::h3_start();
     echo 'Contents';
     html::h3_end();
    }

    $last_menu_depth = 0;
    $first = TRUE;

    $index = $this->get_manual_list();
    if( is_array( $index )) {
      foreach( $index as $row ) {
        extract( $row, EXTR_OVERWRITE );
        foreach( $row as $colname => $colvalue ) {
          $this->$colname = $colvalue;
        }
        $this->construct_default_url();
        $this->set_url();

        if( $first ) {
          if( ! $this->printable_output ) {
            echo $this->get_link_to_printable_index_url();
            html::bullet_point();
            echo $this->get_link_to_whole_manual_url();
            html::horizontal_rule();
            html::new_paragraph();
          }
          html::ulist_start();
          $first = FALSE;
        }

        if(  $menu_depth > $last_menu_depth )
          html::ulist_start();
        elseif(  $menu_depth < $last_menu_depth )
          html::ulist_end();

        if( $this->printable_output )
          $listitem = $order_in_manual . '. ' . $page_title;
        else
          $listitem = $this->get_link_to_current_help_by_page_title( $target = '_self' );

        if( $menu_depth ==  0 ) html::bold_start();

        html::listitem( $listitem );

        if( $menu_depth ==  0 ) html::bold_end();
        $last_menu_depth = $menu_depth;
      }
    }

    while( $last_menu_depth > 0 ) {
      html::ulist_end();
      $last_menu_depth--;
    }

    html::ulist_end();

    if( ! $this->printable_output ) {
      echo $this->get_link_to_printable_index_url();
      html::bullet_point();
      echo $this->get_link_to_whole_manual_url();
      html::bullet_point();
      html::link_to_page_top(  $tabindex=1, $title='Back to top of page' );
    }
    html::new_paragraph();
    html::horizontal_rule();

    if( ! $producing_manual_as_one_file ) {
      html::italic_start();
      $this->aeolus_version_info();
      html::italic_end();
    }
  }
  #-----------------------------------------------------

  function producing_manual_from_commandline_script() {
    if( $_SERVER[ 'PHP_SELF' ] == PRODUCE_MANUAL_FROM_COMMANDLINE 
    ||  $_SERVER[ 'PHP_SELF' ] == PRODUCE_ONE_HELP_FILE_FROM_COMMANDLINE )
      return TRUE;
    else
      return FALSE;
  }
  #-----------------------------------------------------

  function produce_manual() {

    $producing_manual_as_one_file = TRUE;
    $this->write_get_parm( 'printable_output', 'Y' );

    if( $this->producing_manual_from_commandline_script()) {
      html::html_start();
      html::html_head_start();
      html::write_stylesheet( $for_cms_deployment = FALSE, $banner_only = TRUE );
      html::html_head_end();

      html::body_start();
    }

    $this->display_help_index( $producing_manual_as_one_file );

    $index = $this->get_manual_list();
    if( ! is_array( $index )) {
      echo 'No manual entries found.' . NEWLINE;
      return;
    }
    foreach( $index as $row ) {
      extract( $row, EXTR_OVERWRITE );
      foreach( $row as $colname => $colvalue ) {
        $this->$colname = $colvalue;
      }
      $this->construct_default_url();
      $this->set_url();

      $this->write_get_parm( 'page_id', $this->page_id );
      $this->write_get_parm( 'order_in_manual', $this->order_in_manual );

      html::h3_start();
      echo $this->order_in_manual . '. ' . $this->page_title;
      html::h3_end();
      $this->display_help( $producing_manual_as_one_file );
      html::new_paragraph();
    }

    html::italic_start();
    $this->aeolus_version_info();
    html::italic_end();

    if( $this->producing_manual_from_commandline_script()) html::body_end();
  }
  #-----------------------------------------------------

  function produce_one_help_file( $page_id, $order_in_manual, $manual_format ) {

    $this->write_get_parm( 'page_id', $page_id );  # used by display_help()
    $this->write_get_parm( 'order_in_manual', $order_in_manual );

    $producing_manual_as_one_file = FALSE;
    if( $manual_format == 'printable' ) $this->write_get_parm( 'printable_output', 'Y' );

    html::html_start();
    html::html_head_start();
    html::write_stylesheet( $for_cms_deployment = FALSE, $banner_only = TRUE );
    html::html_head_end();
    html::body_start();

    if( $page_id == 'index' )
      $this->display_help_index( $producing_manual_as_one_file );
    else {
      $this->set_help( $page_id, $order_in_manual );
      $this->display_help( $producing_manual_as_one_file );
    }

    html::body_end();
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'button_name':
        return $this->is_alphanumeric_or_blank( $this->parm_value, $allow_underscores = TRUE );

      case 'page_id':
        if( $this->parm_value == 'index' || $this->parm_value == 'manual' )
          return TRUE;
        else 
          return $this->is_integer( $this->parm_value, $allow_negative = TRUE );

      case 'post_help_edit_menu_item_id':
        return $this->is_integer( $this->parm_value, $allow_negative = TRUE );

      case 'order_in_manual':
        return $this->is_integer( $this->parm_value );

      case 'draft_text':
        return $this->is_ok_free_text( $this->parm_value );

      case 'custom_url': 
        # Don't allow any greater than/less than signs
        if( substr_count( $this->parm_value, '>' ) > 0 || substr_count( $this->parm_value, '<' ) > 0 )
          return FALSE;
        return $this->is_ok_free_text( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
