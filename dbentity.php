<?php
/*
 * PHP parent class for all database entities such as JJ collection items, barcodes, containers.
 * Creates a database connection to be passed on to all child classes.
 *
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */

require_once 'dbquery.php';

class DBEntity extends Application_Entity {

  #------------
  # Properties 
  #------------
  var $db_connection;
  var $err_msg;
  var $db_username;

  #-----------------------------------------------------

  function dbentity( &$db_connection ) { 

    # Set up properties from parent class
    $this->Application_Entity();

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $connection_ok = FALSE;
    if( is_object( $db_connection ))
      if( $this->app_get_class( $db_connection ) == 'dbquery' ) $connection_ok = TRUE;
    if( ! $connection_ok ) die( 'A database connection is needed.' );

    $this->db_connection = &$db_connection;
  }
  #-----------------------------------------------------
  function db_run_query( $statement ) {
    $this->db_connection->db_run_query( $statement ) ;
  }
  #-----------------------------------------------------
  function db_select_one_value( $statement ) {
    return $this->db_connection->db_select_one_value( $statement ) ;
  }
  #-----------------------------------------------------
  function db_select_into_array( $statement ) {
    return $this->db_connection->db_select_into_array( $statement ) ;
  }
  #-----------------------------------------------------
  function db_exec_returning_rowcount( $statement ) {
    return $this->db_connection->db_exec_returning_rowcount( $statement ) ;
  }
  #-----------------------------------------------------
  function db_fetch_next_row( $fetch_mode = DB_FETCHMODE_ASSOC ) {
    return $this->db_connection->db_fetch_next_row( $fetch_mode = DB_FETCHMODE_ASSOC ) ;
  }
  #-----------------------------------------------------
  function db_fetch_next_col() {
    return $this->db_connection->db_fetch_next_col() ;
  }
  #-----------------------------------------------------
  function db_get_curr_col_value() {
    return $this->db_connection->db_get_curr_col_value() ;
  }
  #-----------------------------------------------------
  function db_get_curr_col_ident() {
    return $this->db_connection->db_get_curr_col_ident() ;
  }
  #-----------------------------------------------------
  # If statement is passed in, run the statement and fetch the first row into object properties.
  # If no statement is passed in, simply fetch the next row from the previous query.
  # Return DB_OK (1) if a row was fetched, or NULL if rowcount was 0 or you have reached last row of dataset.

  function db_select_into_properties( $statement = NULL ) {

    if( $statement != NULL ) $this->db_connection->db_run_query( $statement );

    $curr_row_array = $this->db_connection->db_fetch_next_row( DB_FETCHMODE_ASSOC );
    if( ! is_array( $curr_row_array )) return NULL;

    foreach( $curr_row_array as $colname => $col_value ) {
      $this->$colname = $col_value;
    }
    return DB_OK;
  }
  #-----------------------------------------------------

  function db_get_username() {

    $statement = 'select user';
    $this->db_username = $this->db_select_one_value( $statement );
    return $this->db_username;
  }
  #-----------------------------------------------------
  function db_help_options_tablename() {

      return $this->get_system_prefix() . '_' . 'help_options';
  }
  #-----------------------------------------------------
  function db_help_pages_tablename() {

      return $this->get_system_prefix() . '_' . 'help_pages';
  }
  #-----------------------------------------------------
  function db_menu_tablename() {

      return $this->get_system_prefix() . '_' . 'menu';
  }
  #-----------------------------------------------------
  function db_report_groups_tablename() {

      return $this->get_system_prefix() . '_' . 'report_groups';
  }
  #-----------------------------------------------------
  function db_report_outputs_tablename() {

      return $this->get_system_prefix() . '_' . 'report_outputs';
  }
  #-----------------------------------------------------
  function db_reports_tablename() {

      return $this->get_system_prefix() . '_' . 'reports';
  }
  #-----------------------------------------------------
  function db_roles_tablename() {

      return $this->get_system_prefix() . '_' . 'roles';
  }
  #-----------------------------------------------------
  function db_sessions_tablename() {

      return $this->get_system_prefix() . '_' . 'sessions';
  }
  #-----------------------------------------------------
  function db_user_roles_tablename() {

      return $this->get_system_prefix() . '_' . 'user_roles';
  }
  #-----------------------------------------------------
  function db_user_saved_queries_tablename() {

      return $this->get_system_prefix() . '_' . 'user_saved_queries';
  }
  #-----------------------------------------------------
  function db_user_saved_query_selection_tablename() {

      return $this->get_system_prefix() . '_' . 'user_saved_query_selection';
  }
  #-----------------------------------------------------
  function db_users_tablename() {

      return $this->get_system_prefix() . '_' . 'users';
  }
  #-----------------------------------------------------
  function db_users_and_roles_viewname() {

      return $this->get_system_prefix() . '_' . 'users_and_roles_view';
  }
  #-----------------------------------------------------

  function db_database_function_name( $core_function_name ) {

    $function_name = 'dbf_' . $this->get_system_prefix() . '_';
    $function_name .= $core_function_name;
    return $function_name;
  }
  #-----------------------------------------------------

  function db_id_seq_name( $table_name ) {

    $seq_name = '';

    switch( $table_name ) {

      default:
        $seq_name = $table_name . '_id_seq';
        break;
    }

    return $seq_name;
  }
  #----------------------------------------------------------------------------------

  ######################################################
  # --- Functions for paginated searching start here ---
  ######################################################
  # --- The primary three functions are:
  # ----- db_search()
  # ----- db_search_results()
  # ----- db_browse()
  # --- Also, EVERY child class needs to override:
  # ----- db_set_search_result_parms()
  # ----- db_set_browse_parms()
  ######################################################
  #
  # Search examples:
  # If searching a view "sample_view":
  # 
  # Define menu option in the menu table based, e.g., on class "sample" and methods:
  # - "search_sample".
  # - "search_sample_results".
  #
  # Within your "sample" class, define methods as follows:
  #
  #  function search_sample() {
  #    $this->db_search( 'sample_view', 'sample', 'search_sample_results' );
  #  }
  #  
  #  function search_sample_results() {
  #    $this->db_search_results();
  #  }
  #
  # function db_set_search_result_parms() {  # overrides parent method from 'dbentity'
  #
  #    $this->search_method  = 'search_sample';
  #    $this->results_method = 'search_sample_results';
  #    $this->from_table     = 'sample_view';
  #    $this->keycol         = 'sample_id';
  #    $this->edit_method    = 'edit_sample';
  #    $this->edit_tab       = '_blank';
  #  }
  #
  # Note that by setting "$this->edit_method" in "db_set_search_result_parms()", you automatically enable
  # an "Edit" button on each record, taking you into the edit method for the class.
  #
  # By default, column names will be used as field labels, but with initial capital and underscores replaced by space.
  # Also all columns from the table/view will be displayed by default. 
  # To change this behaviour, override the method "db_list_columns()" within the child class.
  #
  #-----------------------------------------------------
  # 
  # Browse examples:
  #  
  #  function db_set_browse_parms() {  # Overrides parent method from 'dbentity'
  #    $this->browse_method  = 'browse_sample';
  #    $this->from_table     = 'sample_view';
  #    $this->keycol         = 'sample_id';
  #    $this->edit_method    = 'edit_sample';
  #    $this->edit_tab       = '_blank';
  #  }
  #  
  #  function browse_sample() {
  #    $this->db_browse();
  #  }
  #
  #-----------------------------------------------------

  function db_set_search_result_parms() {  # This method must be overridden in child class to set table name etc

    $this->search_method  = 'db_search';
    $this->results_method = 'db_search_results';
    $this->from_table     = '';
    $this->keycol         = '';

    $this->edit_method    = '';
    $this->edit_tab       = '';  # set this to '_blank' in the child class if you want Edit to take you into new tab
  }
  #-----------------------------------------------------

  function db_set_browse_parms() {  # This method must be overridden in child class to set table name etc

    $this->browse_method  = 'db_browse';
    $this->from_table     = '';
    $this->keycol         = '';

    $this->edit_method    = '';
    $this->edit_tab       = '';  # set this to '_blank' in the child class if you want Edit to take you into new tab
  }
  #-----------------------------------------------------

  function db_search( $table_or_view, $class_name = NULL, $method_name = NULL ) {

    $this->entering_selection_criteria = TRUE;
    $this->reading_selection_criteria = FALSE;

    if( ! $table_or_view ) $this->die_on_error( 'No table/view name passed to DB search.' );

    if( ! $class_name ) $class_name = $this->app_get_class( $this );
    if( ! $method_name ) $method_name = 'db_search_results';
    
    $this->form_name = html::form_start( $class_name, $method_name );

    $this->db_remember_presentation_style(); # Only applicable to logged in users as only they can save to DB.
                                             # If a saved presentation style is found, it is written to POST.

    if( $this->parm_found_in_get( 'simplified_search' ))
      $this->simplified_search = $this->read_get_parm( 'simplified_search' );
    elseif( $this->parm_found_in_post( 'simplified_search' ))
      $this->simplified_search = $this->read_post_parm( 'simplified_search' );

    html::hidden_field( 'simplified_search', $this->simplified_search );
    html::hidden_field( 'manual_search', 'Y' );

    if( $this->calling_form && $this->calling_field ) {
      html::hidden_field( 'calling_form', $this->calling_form );
      html::hidden_field( 'calling_field', $this->calling_field );
    }

    # Identify the columns which can be used in "order by" clause
    $columns = $this->db_list_columns( $table_or_view );  # override in child class if customisation required
    $possible_order_by_cols = $this->db_get_possible_order_by_cols( $columns );

    $order_by = $this->read_post_parm( 'order_by' );
    if( ! $order_by ) {
      $i = 0;
      foreach( $possible_order_by_cols as $row ) {
        $i++;
        foreach( $row as $colnam => $colval ) {
          $o = $colnam;
        }
        if( $i == 1 || $o == DEFAULT_ORDER_BY_COL ) $order_by = $o;
        if( $order_by == DEFAULT_ORDER_BY_COL ) break;
      }
    }

    html::div_start( 'class="buttonrow"' );
    html::submit_button( 'search_button', 'Search' );
    html::submit_button( 'clear_search_button', 'Clear' );

    if( $this->menu_called_as_popup ) {
      $button_label = 'Cancel';
      html::button( 'cancel_search_button', $button_label, $tabindex=1, 'onclick="self.close()"' );
    }
    else {
      echo SPACE;

      # If you want to default to basic search option, override db search method in child class,
      # and write POST parm 'simplified_search' to 'Y' before calling parent db search.
      if( $this->simplified_search == 'Y' ) {
        $simplify = '';
        $button_name = 'Advanced search';
      }
      else {
        $simplify = 'Y';
        $button_name = 'Basic search';
      }

      # If you want to suppress advanced search option, override db search method in child class,
      # and set $this->suppress_advanced_search to TRUE before calling parent db search.
      if( ! $this->suppress_advanced_search ) {
        $script = "onclick='document.$this->form_name.simplified_search.value=" . '"' . $simplify . '"' . "'";
        html::submit_button( 'change_search_level_button', $button_name, $tabindex=1, $script );
        echo ' ';
        html::bullet_point();
        echo ' ';
      }

      html::link( '#searchable_field_list', 'Enter selection' );
      echo ' ';
      html::bullet_point();
      echo ' ';

      html::link( '#presentation_options', 'Change data presentation options' );
      echo LINEBREAK;
    }
    html::div_end( 'buttonrow' );
    html::new_paragraph();

    $this->db_write_query_field_highlight_script();
    $this->db_write_query_op_highlight_script();

    #-------------------------------
    # Allow entry of search criteria
    #-------------------------------
    $this->db_enter_selection_criteria( $from_table, $columns );

    if( $this->menu_called_as_popup ) {  # if in popup search screen, don't clutter up with data presentation options
      $this->db_use_default_presentation_style( $possible_order_by_cols, $default_order_by_col );
    }
    else {
      $this->db_choose_presentation_style( $possible_order_by_cols, $order_by ); # contains submit button
      html::new_paragraph();

      html::div_start( 'class="buttonrow"' );
      html::submit_button( 'search_button', 'Search' );
      html::submit_button( 'clear_search_button', 'Clear' );
      echo SPACE;

      $top_of_form_link = '#searchable_field_list';
      $top_of_form_link_desc = 'Go to top of selection form';

      # 'Top of selection form' link is pointless if form is so small that you can already see top of it!
      if( $this->searchable_field_count < LARGE_SEARCHABLE_FIELD_LIST ) {  
        $top_of_form_link = '#aeolus_page_top_anchor';
        $top_of_form_link_desc = 'Go to top of search form';
      }

      html::link( $top_of_form_link, $top_of_form_link_desc );
      html::div_end( 'buttonrow' );
    }

    html::form_end();
  }
  #-----------------------------------------------------

  function db_list_columns( $table_or_view = NULL ) {  # this should be overridden by child class if required

    if( ! $table_or_view ) return NULL;

    $statement = 'select a.attname, t.typname from pg_attribute a, pg_type t where a.attrelid = '
               . " (select oid from pg_class where relname='$table_or_view') "
               . ' and a.attnum > 0 and a.atttypid = t.oid'
               . ' order by attnum';

    $atts = $this->db_select_into_array( $statement );
    $cols = array();

    foreach( $atts as $rownum => $row ) {
      $colname = $row[ 'attname' ];
      $coltype = $row[ 'typname' ];

      $is_numeric = FALSE;
      $is_date = FALSE;

      switch( $coltype ) {

        case 'date':
        case 'timestamp':
        case 'timestamptz':
        case 'time':
        case 'timetz':
          $is_date = TRUE;
          break;

        case 'numeric':
        case 'int2':
        case 'int4':
        case 'int8':
        case 'float4':
        case 'float8':
          $is_numeric = TRUE;
          break;
      }

      $label = $this->db_get_default_column_label( $colname );

      $cols[] = array( 'column_name'  => $colname,      
                       'column_label' => $label,        
                       'searchable'   => TRUE,
                       'is_numeric'   => $is_numeric, 
                       'is_date'      => $is_date );
    }

    return $cols;

  }
  #-----------------------------------------------------

  function db_get_possible_order_by_cols( $columns ) {

    if( ! $columns ) return NULL;
    $possible_order_by_cols = array();

    foreach( $columns as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      $possible_order_by_cols[] = array( "$column_name" => "$column_label" );
    }

    return $possible_order_by_cols;
  }
  #-----------------------------------------------------

  function db_enter_selection_criteria( $table_or_view, $columns = NULL ) {

    if( ! $columns && ! $table_or_view ) $this->die_on_error( 'No table name passed to DB Enter Selection Criteria' );
    if( ! $columns ) $columns = $this->db_list_columns( $table_or_view );
    if( ! is_array( $columns )) return '1=2';  # will not retrieve any data

    $pressed_clear = FALSE;
    if( $this->parm_found_in_post( 'clear_search_button' )) $pressed_clear = TRUE;

    $this->continue_on_read_parm_err = TRUE;
    $this->entering_selection_criteria = TRUE;

    $date_field_size = 15;  # allow for entry of timestamps such as dd/mm/yyyy hh:mm
    $normal_field_size = $date_field_size + strlen(' From: '); # make column wide enough to prevent ugly wrapping

    $fieldname_heading = 'Field';
    $value_heading = 'Selection';

    if( $this->simplified_search == 'Y' ) 
      $comparison_heading = '';
    else 
      $comparison_heading = 'Comparison';

    html::new_paragraph();
    html::horizontal_rule();

    $this->db_explain_how_to_query();

    $section_headings = $this->db_get_section_headings( $columns );

    html::div_start( 'class="searchablefields"' );
    html::anchor( 'searchable_field_list' );
    $this->searchable_field_count = 0;

    $this->db_enter_selection_criteria_plugin_1();  # dummy method which exists to be overridden by child class

    html::table_start( 'summary="Set of fields in which search criteria may be entered."' );
    html::table_head_start();

    if( $section_headings ) {
      html::tablerow_start();
      html::tabledata_start( 'colspan="4"');
      html::new_paragraph();
      echo 'Sections of this form: ' . $section_headings;
      html::new_paragraph();
      html::tabledata_end();
      html::tablerow_end();
    }

    html::tablerow_start();
    html::column_header( $fieldname_heading,  'class="highlight1 searchablefieldname"' );
    html::column_header( $comparison_heading, 'class="highlight1"' );
    html::column_header( $value_heading,      'class="highlight1"' );
    html::column_header( 'Help/hints',        'class="highlight2"' );
    html::tablerow_end();

    html::table_head_end();
    html::table_body_start();

    $this->in_table = TRUE;
    $this->tabindex = 1;

    foreach( $columns as $row ) {

      $section_heading = '';
      extract( $row, EXTR_OVERWRITE ); # put row into variables
      $column_name2 = $column_name . '2';  # for searches on date ranges

      if( ! $searchable ) continue;
      $this->searchable_field_count++;
      $op = '';

      # We may have picked up a section heading from the "columns" array.
      $this->db_write_section_heading( $section_heading, $cols_in_table = 4 );

      if( ! $pressed_clear ) {
        $this->$column_name = $this->read_post_parm( $column_name );
        $this->$column_name2 = NULL;
        if( $is_date ) $this->$column_name2 = $this->read_post_parm( $column_name2 );
      }

      html::tablerow_start();

      #-------------------
      # 'Fieldname' column
      #-------------------
      $non_text_marker = '';
      if( $is_date || $is_numeric ) $non_text_marker = '*';

      $colon = ':';
      $last_char = substr( $column_label, -1 );
      switch( $last_char ) {
        case ':':
        case '.':
        case '?':
        case '*':
          $colon = '';
          break;
      }

      html::tabledata( $non_text_marker . $column_label . $colon, 'class="searchablefieldname"' );

      #--------------------
      # 'Comparison' column
      #--------------------
      html::tabledata_start();

      if( $is_date ) {
        $op_field = 'date_or_numeric_query_op_' . $column_name; 
        $default_op = DEFAULT_DATE_SEARCH_OP;
        $datatype = 'date';
      }
      elseif( $is_numeric ) {
        $op_field = 'date_or_numeric_query_op_' . $column_name; 
        $default_op = DEFAULT_NUMERIC_SEARCH_OP;
        $datatype = 'numeric';
      }
      else {
        $op_field = 'text_query_op_' . $column_name; 
        $default_op = DEFAULT_TEXT_SEARCH_OP;
        $datatype = 'text';
      }

      if( $this->parm_found_in_post( $op_field )&& ! $pressed_clear ) 
        $op = $this->read_post_parm( $op_field );

      $this->db_select_query_op( $datatype, $op_field, '', $op?$op:$default_op );

      html::tabledata_end();

      #---------------
      # 'Value' column
      #---------------
      html::tabledata_start( 'class="searchablefieldvalue"' );

      if( $this->$column_name == '' && $this->$column_name2 == '' )
        $field_class = '';
      else
        $field_class = ' class="highlight2" ';

      $value_change_script = $this->db_write_query_field_change_script( $column_name );

      $input_parms = $field_class . $value_change_script;


      $secondary_label = '';
      $field_size = $normal_field_size;
      if( $is_date ) {
        $secondary_label = 'From';
        $field_size = $date_field_size;  # allow for entry of timestamps such as dd/mm/yyyy hh:mm
      }

      # Method input_field does an "htmlentities" on the field value before writing it out.
      html::input_field( $column_name, $secondary_label, $this->strip_all_slashes( $this->$column_name ),
                         $in_table = FALSE, $size = $field_size, 
                         $tabindex = 1, $label_parms = NULL, $data_parms = NULL, 
                         $input_parms = $input_parms );

      if( $is_date ) {
        $this->searchable_field_count++;
        echo LINEBREAK;
        $secondary_label = 'To';
        html::input_field( $column_name2, $secondary_label, $this->strip_all_slashes( $this->$column_name2 ),
                           $in_table = FALSE, $size = $field_size, 
                           $tabindex = 1, $label_parms = NULL, $data_parms = NULL, 
                           $input_parms = str_replace( $column_name, $column_name2, $input_parms ));
      }

      $this->db_enter_selection_criteria_plugin_2(  $column_name, $$column_name );  # child class may override
      html::tabledata_end();

      #--------------------
      # 'Help/hints' column
      #--------------------
      html::tabledata_start();
      #if( $trailing_text ) echo $trailing_text . ' ';  # trailing text is more designed for data entry
      if( $search_help_text ) echo $search_help_text . ' ';
      if( $search_help_text && $search_help_class ) echo LINEBREAK;
      if( $search_help_class ) {
        $help_obj = new $search_help_class( $this->db_connection );
        $help_obj->publicly_available_page = $this->publicly_available_page;
        if( method_exists( $help_obj, 'desc_dropdown' ))
          $help_obj->desc_dropdown( $this->form_name, 'help_' . $column_name, $column_name );
        elseif( method_exists( $help_obj, 'launch_search_popup' ))
          $help_obj->launch_search_popup( $calling_form=$this->form_name, $calling_field=$column_name );
      }
      $this->db_enter_selection_criteria_plugin_3(  $column_name, $$column_name );  # child class may override
      html::tabledata_end();

      html::tablerow_end();
    }

    html::table_body_end();
    html::table_end();

    html::new_paragraph();
    if( $section_headings ) {
      echo 'Sections of this form: ' . $section_headings;
      html::new_paragraph();
    }

    html::div_end( 'searchablefields' ); # end of searchablefields div

    $this->entering_selection_criteria = FALSE; # finished entering selection criteria
  }
  #-----------------------------------------------------
  function db_enter_selection_criteria_plugin_1() {}  # dummy methods which exist to be overridden by child class
  function db_enter_selection_criteria_plugin_2(  $column_name = NULL, $column_value = NULL ) {}
  function db_enter_selection_criteria_plugin_3(  $column_name = NULL, $column_value = NULL ) {}
  
  function db_browse_plugin_1( $column_name = NULL, $column_value = NULL ) {}
  function db_browse_plugin_1a( $column_name = NULL, $column_value = NULL ) {}
  function db_browse_plugin_2( $column_name = NULL, $column_value = NULL ) {}

  function db_search_results_plugin_1() {} 
  #-----------------------------------------------------

  function db_browse_reformat_data( $column_name = NULL, $column_value = NULL ) {  # override in child class 
    return $column_value;
  }
  #-----------------------------------------------------

  function db_get_section_headings( $columns = NULL ) {
    
    $this->total_section_headings = 0;
    $this->curr_section_heading_no = 0;  # Will be used later for checking which heading we are currently on.

    if( ! is_array( $columns )) return NULL;

    $section_links = array();
    $link_count = 0;

    foreach( $columns as $row ) {
      if( $row[ 'section_heading' ] ) {
        $section_heading = $row[ 'section_heading' ];
        $already_got_heading = key_exists( $section_heading, $section_links );

        if( $already_got_heading ) continue;

        $link_count++;
        $link_name = 'section' . $link_count;
        $this_link = html::return_link( '#' . $link_name, $section_heading, $section_heading );

        $section_links[ "$section_heading" ] = $this_link;
      }
    }

    $this->total_section_headings = $link_count;

    return implode( ' |', $section_links );
  }
  #-----------------------------------------------------

  function db_write_section_heading( $section_heading = NULL, $cols_in_table = 1, $heading_class = 'sectionhead',
                                     $section_link_class = NULL, $include_horizontal_rules = TRUE ) {

    if( $section_heading ) {  # Display section heading if one exists
      $this->curr_section_heading_no++;
      html::tablerow_start();
      if( $this->curr_section_heading_no > 1 && $include_horizontal_rules ) {
        html::tabledata_start( 'colspan="' . $cols_in_table . '"' );
        html::horizontal_rule();
        html::tabledata_end();
        html::new_tablerow();
      }

      $colspan = $cols_in_table;
      if( $cols_in_table > 1 ) $colspan = $colspan - 1;
      $tabledata_parm = NULL;
      if( $heading_class ) $tabledata_parm = ' class="' . $heading_class . '" ';

      html::tabledata_start( $tabledata_parm . ' colspan="' . $colspan . '"' );
      html::anchor( 'section' . $this->curr_section_heading_no );
      echo $section_heading;
      $last_char = substr( $section_heading, -1 );
      if( $last_char != ':' && $last_char != '.' && $last_char != '?' && $last_char != '*' )
        echo ':';
      if( $cols_in_table > 1 ) 
        html::tabledata_end();
      else
        echo SPACE . SPACE . SPACE;

      $section_link_class .= ' rightaligned';
      $tabledata_parm = ' class="' . $section_link_class . '" ';
      if( $cols_in_table > 1 ) html::tabledata_start( $tabledata_parm );
      $next_section = '';
      $prev_section = '';
      if( $this->curr_section_heading_no < $this->total_section_headings ) {
        $next_section_no = $this->curr_section_heading_no + 1;
        $next_section = 'section' . $next_section_no;
        $link_to_next = html::return_link( '#' . $next_section, 'Next section', 'Next section' );
      }
      if( $this->curr_section_heading_no > 1 ) {
        $prev_section_no = $this->curr_section_heading_no - 1;
        $prev_section = 'section' . $prev_section_no;
        $link_to_prev = html::return_link( '#' . $prev_section, 'Prev section', 'Prev section' );
      }
      if( $next_section ) echo $link_to_next;
      if( $next_section && $prev_section ) echo '| ';
      if( $prev_section ) echo $link_to_prev;
      html::tabledata_end();
      html::tablerow_end();
    }
  }
  #-----------------------------------------------------

  function db_explain_how_to_query() {

    if( $this->menu_called_as_popup ) {
      html::italic_start();
      echo 'Enter selection, and/or change the Comparison dropdown as required.';
      html::italic_end();
      html::new_paragraph();
      return;
    }

    html::italic_start();
    echo 'Enter selection, and/or change the Comparison dropdown as required. Please note:';
    html::new_paragraph();

    html::ulist_start();

    html::listitem( 'Any non-text fields (dates or numbers) are marked with an asterisk. You can search dates '
         . " and numbers using the 'less than' or 'greater than' options from the Comparison dropdown, "
         . ' e.g. to find records containing dates before 1800 or numbers greater than zero.' );

    html::listitem('You can search text fields by entering a word or phrase which appears within the name '
                   . ' or description being searched for.');

    html::listitem("You do not have to match the case of text fields, i.e. 'oxford' is equivalent to 'Oxford'.");

    html::listitem(
         "You can use the wildcard '%' (percent sign) to represent any number of characters of a text field."
         . " E.g. the entry 'bod%lib%' could stand for 'Bodleian Library', 'Bodleian Law Library'"
         . " and 'Bodley's Librarian'.");

    html::listitem( 
    "To find records where a particular field is blank, choose 'is blank' from the Comparison dropdown list. " .
    "Choose 'is not blank' to find records where that field is not blank."); 

    html::ulist_end();
    html::italic_end();
    html::new_paragraph();
  }
  #-----------------------------------------------------

  function db_select_query_op( $datatype, $fieldname, $label, $selected_value = NULL, $in_table = FALSE ) { 

    if( $this->simplified_search == 'Y' ) {
      html::hidden_field( $fieldname, $selected_value );
      return;
    }

    if( $datatype == 'date' )
      $ops = $this->db_list_date_query_ops();
    elseif( $datatype == 'numeric' )
      $ops = $this->db_list_numeric_query_ops();
    else
      $ops = $this->db_list_text_query_ops();

    $parms = $this->db_write_op_change_script( $fieldname );

    if( $selected_value == 'is_blank' || $selected_value == 'is_not_blank' )
      $parms = $parms . ' class="highlight2" ';

    html::dropdown_start( $fieldname, $label, $in_table, $parms );

    foreach( $ops as $internal_value => $displayed_value ) {
      html::dropdown_option( $internal_value, $displayed_value, $selected_value );
    }

    html::dropdown_end();
  }
  #-----------------------------------------------------

  function db_list_text_query_ops() {

    $text_query_ops = array(
      'contains'        => 'contains',
      'starts_with'     => 'starts with',
      'ends_with'       => 'ends with',
      'equals'          => 'equals',
      'does_not_contain'   => 'does not contain',
      'does_not_start_with'=> 'does not start with',
      'does_not_end_with'  => 'does not end with',
      'is_not_equal_to' => 'is not equal to',
      'is_blank'        => 'is blank',
      'is_not_blank'    => 'is not blank'
    );

    return $text_query_ops;
  }
  #-----------------------------------------------------

  function db_list_numeric_query_ops() {

    $numeric_query_ops = array(
      'less_than'                => 'less than',
      'less_than_or_equal_to'    => 'less than or equal to',
      'equals'                   => 'equals',
      'greater_than_or_equal_to' => 'greater than or equal to',
      'greater_than'             => 'greater than',
      'is_not_equal_to'          => 'is not equal to',
      'is_blank'                 => 'is blank',
      'is_not_blank'             => 'is not blank'
    );

    return $numeric_query_ops;
  }
  #-----------------------------------------------------

  function db_list_date_query_ops() {

    $date_query_ops = array(
      'in_range'      => 'in range',
      'is_blank'      => 'is blank',
      'is_not_blank'  => 'is not blank'
    );

    return $date_query_ops;
  }
  #-----------------------------------------------------

  function db_write_op_change_script( $fieldname = NULL ) {

    $onchange_script = ' onchange="highlightQueryOp( this )" ';
    return $onchange_script;
  }

  #-----------------------------------------------------

  function db_get_sql_query_op ( $op_value ) {

    switch( $op_value ) {

      case 'equals':
        return ' = ';

      case 'contains':
      case 'starts_with':
      case 'ends_with':
        return ' like ';

      case 'does_not_contain':
      case 'does_not_start_with':
      case 'does_not_end_with':
        return ' not like ';

      case 'is_not_equal_to':
        return ' != ';

      case 'less_than':
        return ' < ';

      case 'less_than_or_equal_to':
        return ' <= ';

      case 'greater_than_or_equal_to':
        return ' >= ';

      case 'greater_than':
        return ' > ';

      case 'in_range':       # N.B. will always need to check that they haven't entered only ONE END of range.
        return ' between ';  # If they've only entered one end of range, use >= or <= instead.

      default:
        return '';
    }
  }
  #-----------------------------------------------------

  function db_is_query_op( $the_value ) {

    for( $i = 1; $i <= 3; $i++ ) {
      switch( $i ) {
        case 1:
          $ops = $this->db_list_text_query_ops();
          break;
        case 2:
          $ops = $this->db_list_date_query_ops();
          break;
        case 3:
          $ops = $this->db_list_numeric_query_ops();
          break;
      }
      foreach( $ops as $internal_value => $displayed_value ) {
        if( $the_value == $internal_value )
          return TRUE;
      }
    }

    return FALSE;
  }
  #-----------------------------------------------------

  function db_get_query_op_description( $the_value ) {

    if( $the_value == 'equals' ) return '=';

    for( $i = 1; $i <= 3; $i++ ) {
      switch( $i ) {
        case 1:
          $ops = $this->db_list_text_query_ops();
          break;
        case 2:
          $ops = $this->db_list_date_query_ops();
          break;
        case 3:
          $ops = $this->db_list_numeric_query_ops();
          break;
      }
      foreach( $ops as $internal_value => $displayed_value ) {
        if( $the_value == $internal_value )
          return $displayed_value;
      }
    }

    return '';
  }
  #-----------------------------------------------------

  function db_write_query_field_highlight_script() {

    $script = 'function highlightQueryField( theQueryField ) {'           . NEWLINE
                     . '  var val = theQueryField.value;'                 . NEWLINE
                     . "  if( val == '' ) {"                              . NEWLINE
                     . "    theQueryField.className = '';"                . NEWLINE
                     . '  }'                                              . NEWLINE
                     . '  else {'                                         . NEWLINE
                     . "    theQueryField.className = 'highlight2';"      . NEWLINE
                     . '  }'                                              . NEWLINE
                     . '}'                                                . NEWLINE;

    html::write_javascript_function( $script );
  }
  #-----------------------------------------------------

  function db_write_query_op_highlight_script() {

    $script = 'function highlightQueryOp( theQueryOp ) {'                       . NEWLINE
                     . '  var val = theQueryOp.value;'                          . NEWLINE
                     . "  if( val == 'is_blank' || val == 'is_not_blank' )  {"  . NEWLINE
                     . "    theQueryOp.className = 'highlight2';"               . NEWLINE
                     . '  }'                                                    . NEWLINE
                     . '  else {'                                               . NEWLINE
                     . "    theQueryOp.className = '';"                         . NEWLINE
                     . '  }'                                                    . NEWLINE
                     . '}'                                                      . NEWLINE;

    html::write_javascript_function( $script );
  }
  #-----------------------------------------------------

  function db_write_query_field_change_script( $column_name = NULL ) {

    $onchange_script = ' onchange="highlightQueryField( this )" ';
    return $onchange_script;
  }
  #-----------------------------------------------------
  # You can change defaults by writing POST values beforehand

  function db_choose_presentation_style( $possible_order_by_cols = NULL, $default_order_by_col = '1' ) {

    html::new_paragraph();
    html::anchor( 'presentation_options' );

    html::div_start( 'class="choosepresentation"');
    
    html::div_start( 'class="bold"');
    echo 'Data presentation options: ';
    html::submit_button( 'change_data_presentation', 'Change' );
    html::div_end( 'bold' );
    
    if( $this->publicly_available_page ) # CMS doesn't seem to like blank lines, and suppresses them!!!
      html::horizontal_rule();
    html::new_paragraph();

    $this->db_choose_order_by_col( $possible_order_by_cols, $default_order_by_col );

    if( $this->publicly_available_page ) html::new_paragraph();

    $this->db_choose_asc_desc();
    html::new_paragraph();

    $this->db_choose_entries_per_page();
    html::new_paragraph();

    $this->db_choose_results_output_style();

    html::div_end( 'choosepresentation' );
    html::new_paragraph();
  }
  #-----------------------------------------------------
  # You can change defaults by writing POST values beforehand

  function db_use_default_presentation_style( $possible_order_by_cols = NULL, $default_order_by_col = '1' ) {  

    $order_by = $this->read_post_parm( 'order_by' );

    if( ! $order_by && $default_order_by_col && $default_order_by_col != '1' ) 
      $order_by = $default_order_by_col;

    if( ! $order_by ) {
      if( is_array( $possible_order_by_cols ) && count( $possible_order_by_cols ) > 0 ) {
        $columndetails = $possible_order_by_cols[0];
        if( is_array( $columndetails ) && count( $columndetails ) > 0 ) {
          foreach( $columndetails as $column_name => $column_label ) {
            $order_by = $column_name;
            break;
          }
        }
      }
    }

    if( ! $order_by ) $order_by = '1';
    html::hidden_field( 'order_by', $order_by );

    $this->sort_descending = DEFAULT_DESCENDING;
    if( $this->parm_found_in_post( 'sort_descending' ))
      $this->sort_descending = $this->read_post_parm( 'sort_descending' );
    html::hidden_field( 'sort_descending', $this->sort_descending );

    $this->record_layout = $this->read_post_parm( 'record_layout' );
    if( ! $this->record_layout ) $this->record_layout = DEFAULT_RECORD_LAYOUT;
    html::hidden_field( 'record_layout', $this->record_layout );

    $this->entries_per_page = $this->read_post_parm( 'entries_per_page' );
    if( ! $this->entries_per_page ) $this->entries_per_page = DEFAULT_ENTRIES_PER_BROWSE_PAGE;
    if( $this->entries_per_page > MAX_ENTRIES_PER_BROWSE_PAGE ) 
      $this->entries_per_page = MAX_ENTRIES_PER_BROWSE_PAGE;
    html::hidden_field( 'entries_per_page', $this->entries_per_page );
  }
  #-----------------------------------------------------

  function db_choose_order_by_col( $possible_order_by_cols = NULL, $default_order_by_col = '1' ) {

    $order_by = $this->read_post_parm( 'order_by' );
    if( ! $order_by ) $order_by = $default_order_by_col;

    if( is_array( $possible_order_by_cols )) {

      html::dropdown_start( 'order_by', 'Sort data by' );

      foreach( $possible_order_by_cols as $row ) {
        foreach( $row as $column_name => $column_label ) {
          html::dropdown_option( $column_name, $column_label, $order_by );
        }
      }

      html::dropdown_end();
    }
  }
  #-----------------------------------------------------

  function db_choose_asc_desc() {

    $this->sort_descending = DEFAULT_DESCENDING;

    if( $this->parm_found_in_post( 'sort_descending' ))
      $this->sort_descending = $this->read_post_parm( 'sort_descending' );

    echo 'Order: ';

    html::span_start( 'class="narrowspaceonleft"' );
    html::radio_button( 'sort_descending', 'Ascending', 0, $this->sort_descending,
                        $tabindex = 1, $button_instance = 1 );
    html::span_end( 'narrowspaceonleft' );

    html::span_start( 'class="narrowspaceonleft"' );
    html::radio_button( 'sort_descending', 'Descending', 1, $this->sort_descending,
                        $tabindex = 1, $button_instance = 2);
    html::span_end( 'narrowspaceonleft' );
  }
  #-----------------------------------------------------

  function db_choose_results_output_style() {

    $this->record_layout = $this->read_post_parm( 'record_layout' );
    if( ! $this->record_layout ) $this->record_layout = DEFAULT_RECORD_LAYOUT;

    echo 'Record layout: ';

    html::span_start( 'class="narrowspaceonleft"' );
    html::radio_button( 'record_layout', 'Across the page', 'across_page', $this->record_layout,
                        $tabindex = 1, $button_instance = 1 );
    html::span_end( 'narrowspaceonleft' );

    html::span_start( 'class="narrowspaceonleft"' );
    html::radio_button( 'record_layout', 'Down the page', 'down_page', $this->record_layout,
                        $tabindex = 1, $button_instance = 2);
    html::span_end( 'narrowspaceonleft' );
  }
  #-----------------------------------------------------

  function db_choose_entries_per_page() {

    $this->entries_per_page = $this->read_post_parm( 'entries_per_page' );
    if( ! $this->entries_per_page ) $this->entries_per_page = DEFAULT_ENTRIES_PER_BROWSE_PAGE;
    if( $this->entries_per_page > MAX_ENTRIES_PER_BROWSE_PAGE ) 
      $this->entries_per_page = MAX_ENTRIES_PER_BROWSE_PAGE;

    html::input_field( 'entries_per_page', 'Records per page', $this->entries_per_page,
                       $in_table = FALSE, $size = 2 );

    echo ' (max ' . MAX_ENTRIES_PER_BROWSE_PAGE . ')';
  }
  #-----------------------------------------------------

  function db_read_presentation_style() {

    $this->order_by = $this->read_post_parm( 'order_by' );
    if( ! $this->order_by ) $this->order_by = DEFAULT_ORDER_BY_COL;

    $this->sort_descending = $this->read_post_parm( 'sort_descending' );
    if( ! $this->sort_descending ) $this->sort_descending = DEFAULT_DESCENDING;

    $this->entries_per_page = $this->read_post_parm( 'entries_per_page' );
    if( ! $this->entries_per_page ) $this->entries_per_page = DEFAULT_ENTRIES_PER_BROWSE_PAGE;
    if( $this->entries_per_page > MAX_ENTRIES_PER_BROWSE_PAGE ) 
      $this->entries_per_page = MAX_ENTRIES_PER_BROWSE_PAGE;

    $this->record_layout = $this->read_post_parm( 'record_layout' );
    if( ! $this->record_layout ) $this->record_layout = DEFAULT_RECORD_LAYOUT;

    return $this->order_by;
  }
  #-----------------------------------------------------

  function db_remember_presentation_style() {  

    #----------------------------------------------------------------
    # Only applicable to users who are logged in.
    # Not applicable for popup windows where sort order is predefined
    #----------------------------------------------------------------
    if( $this->publicly_available_page || $this->menu_called_as_popup || ! $this->menu_item_id ) return;

    #--------------------------------------------------------------
    # Write presentation options from previous query back into POST
    #--------------------------------------------------------------
    if( ! $this->parm_found_in_post( 'order_by' )) {  # if 'order by' hasn't been chosen, no options have been chosen

      $found = $this->db_get_saved_presentation_style();

      if( $found ) {
        $this->write_post_parm( 'order_by',         $this->query_order_by );
        $this->write_post_parm( 'sort_descending',  $this->query_sort_descending );
        $this->write_post_parm( 'entries_per_page', $this->query_entries_per_page );
        $this->write_post_parm( 'record_layout',    $this->query_record_layout );
      }
    }
  }
  #-----------------------------------------------------

  function db_get_saved_presentation_style() {  

    # Only applicable to logged in users, as only they can save to DB.
    if( $this->publicly_available_page || $this->menu_called_as_popup || ! $this->menu_item_id ) 
      return NULL;

    $this->query_id = NULL;

    $statement = 'select query_id, '
               . ' query_order_by, query_sort_descending, query_entries_per_page, query_record_layout '
               . ' from ' . $this->db_user_saved_queries_tablename() . ' ' 
               . ' where ' . $this->db_where_clause_for_presentation_style();

    $this->db_select_into_properties( $statement );

    return $this->query_id;
  }
  #-----------------------------------------------------

  function db_where_clause_for_presentation_style() {  

    if( $this->search_method ) # you are probably in results method, but save for search method
      $search_method = $this->search_method;

    else  # you should be in search method already, so use its name
      $search_method = $this->menu_method_name;

    $where_clause = " username = '$this->username' "
                  . " and username > '' "
                  . " and query_class = '$this->menu_class_name' "
                  . " and query_method = '$search_method' "
                  . ' and query_id < 0';  # IDs less than 0 are not displayed in 'Your own saved queries' page

    return $where_clause;
  }
  #-----------------------------------------------------

  function db_save_presentation_style() {

    #--------------------------------------------------------------------------------
    # Do not attempt to write to database in the case of users who are not logged in.
    # Also do not do this for popup windows where the sort order is predefined.
    #--------------------------------------------------------------------------------
    if( $this->publicly_available_page || $this->menu_called_as_popup || ! $this->menu_item_id ) return;

    #---------------------------------------------------------------
    # Write a record of latest choice of 'order by' column etc to DB
    #---------------------------------------------------------------
    $query_id = $this->db_get_saved_presentation_style();

    if( $query_id ) { # record already exists for preferences for this menu option

      if( $this->query_order_by         != $this->order_by
      ||  $this->query_sort_descending  != $this->sort_descending
      ||  $this->query_entries_per_page != $this->entries_per_page
      ||  $this->query_record_layout    != $this->record_layout ) {

        $statement = 'update ' . $this->db_user_saved_queries_tablename() . ' set '
                   . " query_order_by         = '" . $this->order_by . "', "
                   . " query_sort_descending  = $this->sort_descending, "
                   . " query_entries_per_page = $this->entries_per_page, "
                   . " query_record_layout    = '" . $this->record_layout . "' "
                   . ' where ' . $this->db_where_clause_for_presentation_style();
        $this->db_run_query( $statement );
      }
    }
    else { # create new record
      $sequence_name = $this->db_id_seq_name( $this->db_user_saved_queries_tablename() );
      $statement = "select nextval( '$sequence_name'::regclass )";
      $query_id = $this->db_select_one_value( $statement );
      $query_id = 0 - $query_id;  # use negative IDs for these hidden values

      $statement = 'insert into ' . $this->db_user_saved_queries_tablename() . ' ( '
                 . ' query_id, '
                 . ' username, '
                 . ' query_class, '
                 . ' query_method, '
                 . ' query_title, '
                 . ' query_menu_item_name, '
                 . ' query_order_by, '
                 . ' query_sort_descending, '
                 . ' query_entries_per_page, '
                 . ' query_record_layout '

                 . ' ) values ( '

                 . " $query_id, "
                 . " '$this->username', "
                 . " '$this->menu_class_name', "
                 . " '$this->search_method', "
                 . " 'Data presentation options for latest query', "
                 . " '" . $this->escape( $this->menu_item_name ) . "', "
                 . " '$this->order_by', "
                 . " $this->sort_descending, "
                 . " $this->entries_per_page, "
                 . " '$this->record_layout' "

                 . ')';
      $this->db_run_query( $statement );
    }
  }
  #-----------------------------------------------------

  function db_search_results() {

    $this->entering_selection_criteria = FALSE;
    $this->reading_selection_criteria = TRUE;

    $this->db_set_search_result_parms(); # must be overridden in child class

    if( ! $this->from_table || ! $this->keycol ) {
      if( $this->debug )
        $this->die_on_error( 'Table name and key column must be set in DB Set Search Result Parms' );
      else
        die();
    }

    $search_method  = $this->search_method;
    $results_method = $this->results_method;
    $from_table     = $this->from_table;
    $keycol         = $this->keycol;

    $class_name = $this->app_get_class( $this );

    $this->simplified_search = $this->read_post_parm( 'simplified_search' );

    if( $this->parm_found_in_post( 'clear_search_button' )) {
      $this->$search_method( $from_table, $class_name );
      return;
    }

    if( $this->parm_found_in_post( 'change_search_level_button' )) {
      $this->$search_method( $from_table, $class_name );
      return;
    }

    if( $this->parm_found_in_post( 'save_query' )) {
      $this->db_save_query();
      return;
    }

    # CSV/printable output parm is read by the "menu" object.
    # However, if in a CMS search screen, you won't have gone through menu
    if( $this->publicly_available_page ) $this->printable_output = $this->read_post_parm( 'printable_output' );
    if( $this->publicly_available_page ) $this->csv_output = $this->read_post_parm( 'csv_output' );

    if( ! $this->csv_output && ! $this->printable_output ) {
      $this->db_read_presentation_style();  # find out any pre-set values for entries per page, etc.

      $this->db_save_presentation_style(); # only applies to logged in users

      html::div_start( 'class="buttonrow"' );

      $this->db_search_results_plugin_1();

      # Some popup screens, e.g. Offical Papers keyword list, go straight into search results when first called,
      # so no need to show 'Refine Search for these.
      $auto_searched = FALSE;
      if( ! $this->parm_found_in_post( 'search_button' )
      &&  ! $this->parm_found_in_post( 'manual_search' )
      &&  ! $this->parm_found_in_post( 'change_data_presentation' ))
        $auto_searched = TRUE;

      if( ! $auto_searched ) {  
        html::form_start( $class_name, $search_method );
        $this->db_write_new_query_fields();
        $button_name = 'Refine Search';
        html::submit_button( 'change_search_button', $button_name );
        html::form_end();
      }

      echo ' ';

      html::form_start( $class_name, $search_method );
      $this->db_write_new_query_fields();
      $button_name = 'New Search';
      if( $auto_searched ) $button_name = 'Search';
      html::submit_button( 'clear_search_button', $button_name );

      if( $this->menu_called_as_popup ) {
        echo ' ';
        $button_name = 'Cancel';
        html::button( 'cancel_search_button', $button_name, $tabindex=1, 'onclick="self.close()"' );
      }
      html::form_end();
      echo LINEBREAK;
      html::div_end( 'buttonrow' );
    }

    html::new_paragraph();

    $columns = $this->db_list_columns( $this->from_table );

    $selection_cols = $this->db_get_selection_cols( $columns );

    $order_by = $this->db_read_presentation_style();

    $where_clause = $this->db_read_selection_criteria( $columns );

    #----------------------------------------------------------------------------
    # If there is going to be more than one page of data, decide which you are on
    #----------------------------------------------------------------------------
    $results = NULL;
    if( $this->failed_validation )
      foreach( $this->app_errmsgs as $errfield => $msg ) {
        $this->display_errmsg( $errfield, $msg );
      }
    else
      $results=$this->db_select_one_page_into_array( $class_to_call   = $class_name,
                                                     $method_to_call  = $results_method,
                                                     $selection_cols  = $selection_cols, 
                                                     $keycol          = $keycol, 
                                                     $from_tables     = $from_table,
                                                     $where_clause    = $where_clause, 
                                                     $order_by        = $order_by,
                                                     $entries_per_page= $this->entries_per_page,
                                                     $sort_descending = $this->sort_descending  );

    #-----------------
    # Display the data
    #-----------------
    if( ! is_array( $results )) {
      html::new_paragraph();
      echo 'No data found matching your selection criteria.';
      html::new_paragraph();
    }
    else {
      if( $this->csv_output ) 
        $this->db_produce_csv_output( $results );

      elseif( $this->record_layout == 'across_page' 
      && ( $this->force_printing_across_page || ! $this->printable_output )) 
        $this->db_browse_across_page( $results, $columns );

      else
        $this->db_browse_down_page( $results, $columns );
    }

    if( ! $this->csv_output && ! $this->printable_output ) {
      $this->db_write_page_selection_buttons();
    }
  }
  #-----------------------------------------------------

  function db_get_selection_cols( $columns = NULL ) {

    # Historically, we had different handling for CSV and other output (CSV output dumps out everything in the array,
    # whereas printable/onscreen output uses the list of valid columns to decide at a later stage which to print).
    # However, now that the 'db_get_selection_cols' method exists, the child class can simply override it.

    $selection_cols = '*';

    if( is_array( $columns )) {
      if( $this->csv_output ) {
        $selection_cols = '';
        foreach( $columns as $crow ) {
          if( $selection_cols ) $selection_cols .= ', ';
          $selection_cols .= $crow[ 'column_name' ];
        }
      }
    }

    return $selection_cols;
  }
  #-----------------------------------------------------

  function db_query_on_date_val( &$criteria_desc,  # by reference
                                 $column_name,
                                 $column_value,
                                 $column_name2,
                                 $column_value2,
                                 $column_label,
                                 $op ) {

    $where_clause_section = '';
    $criterion = '';

    if(( strval( $column_value ) > '' || strval( $column_value2 ) > '' )
    && $op != 'is_blank' && $op != 'is_not_blank' ) {

      $criterion = $column_label;

      if( $column_value )
        $non_null_column = " coalesce( $column_name, " . DEFAULT_NULL_REPLACEMENT_DATE_EARLY . " ) ";
      else
        $non_null_column = " coalesce( $column_name, " . DEFAULT_NULL_REPLACEMENT_DATE_LATE . " ) ";
      $where_clause_section = " and $non_null_column ";

      $converted_column_value  = $this->db_convert_date_string_for_query( $column_value );
      $converted_column_value2 = $this->db_convert_date_string_for_query( $column_value2 );

      if( $converted_column_value && $converted_column_value2 ) {
        $criterion .= " from $column_value to $column_value2";
        $where_clause_section .= " between $converted_column_value and $converted_column_value2  ";
      }
      elseif( $converted_column_value ) {
        $criterion .= " from $column_value onwards";
        $where_clause_section .= " >= $converted_column_value ";
      }
      else {
        $criterion .= " up to $column_value2";
        $where_clause_section .= " <= $converted_column_value2  ";
      }
    }

    elseif( $op == 'is_blank' || $op == 'is_not_blank' ) {
      $criterion = $column_label . ' ' . $this->db_get_query_op_description( $op ); 

      if( $op == 'is_blank' )
        $where_clause_section = " and $column_name is null ";
      else
        $where_clause_section = " and $column_name is not null ";
    }

    if( $criterion ) $criteria_desc[] = $criterion;

    return $where_clause_section;
  }
  #-----------------------------------------------------

  function db_convert_date_string_for_query( $date_string ) {

    if( strlen( $date_string ) > strlen( 'dd/mm/yyyy' ))
      $converted_date_string = "'" . $date_string . "'" . '::timestamp ';

    elseif( strlen( $date_string ) > '' )
      $converted_date_string = "'" . $date_string . "'" . '::date ';

    return $converted_date_string;
  }
  #-----------------------------------------------------
  function db_read_selection_criteria( $columns = NULL ) {

    if( ! $columns ) $columns = $this->db_list_columns( $this->from_table );
    if( ! is_array( $columns )) return '1=2';  # will not retrieve any data

    $this->reading_selection_criteria = TRUE;

    $where_clause = '1=1';

    $criteria_desc = array();          # this array simply provides a user-friendly *description* of the query
    $this->savable_criteria = array(); # this array lists names/values of hidden fields and operators

    $order_by = $this->read_post_parm( 'order_by' );
    $order_descrip = '';
    $sort_descending = DEFAULT_DESCENDING;
    if( $this->parm_found_in_post( 'sort_descending' )) 
      $sort_descending = $this->read_post_parm( 'sort_descending' );
    $colno = 0;
 
    foreach( $columns as $row ) {

      $colno++;
      extract( $row, EXTR_OVERWRITE ); # put row into variables

      if(( $column_name == $order_by ) || ( $column_name == 'displayonly_' . $order_by )
      || ( $order_by == '1' && $colno == 1 ))
        $order_descrip = $column_label;

      if( ! $searchable ) continue;

      # For dates only, enable searching of ranges
      $column_name2 = $column_name . '2';
      $column_value2 = NULL;

      $column_value = strval( $this->read_post_parm( $column_name ));
      if( $is_date && $this->parm_found_in_post( $column_name2 )) 
        $column_value2 = strval( $this->read_post_parm( $column_name2 ));

      $op = '';
      if( $is_date || $is_numeric )
        $op_field = 'date_or_numeric_query_op_' . $column_name; 
      else
        $op_field = 'text_query_op_' . $column_name; 
      if( $this->parm_found_in_post( $op_field )) $op = $this->read_post_parm( $op_field );


      $querying_this_column = FALSE;
      if( strval( $column_value ) > '' 
      ||  strval( $column_value2 ) > '' 
      ||  $op == 'is_blank' 
      ||  $op == 'is_not_blank' ) $querying_this_column = TRUE;
      if( ! $querying_this_column ) continue;

      #------------
      # DATE FIELDS
      #------------
      if( $is_date ) {   

        $where_clause .= $this->db_query_on_date_val( $criteria_desc,  # by reference
                                                      $column_name,
                                                      $column_value,
                                                      $column_name2,
                                                      $column_value2,
                                                      $column_label,
                                                      $op );
      }


      #---------------
      # NUMERIC FIELDS
      #---------------
      elseif( $is_numeric )  {   

        if( strval( $column_value ) > '' && $op != 'is_blank' && $op != 'is_not_blank' ) {
          $criteria_desc[] = $column_label . ' ' . $this->db_get_query_op_description( $op ) . ' ' . $column_value; 

          $sql_op = $this->db_get_sql_query_op( $op );

          $where_clause = $where_clause . " and coalesce( $column_name, " . DEFAULT_NULL_REPLACEMENT_INT
                                        . " ) $sql_op $column_value ";
        }

        elseif( $op == 'is_blank' || $op == 'is_not_blank' ) {
          $criteria_desc[] = $column_label . ' ' . $this->db_get_query_op_description( $op ); 

          if( $op == 'is_blank' )
            $where_clause = $where_clause . " and $column_name is null ";
          else
            $where_clause = $where_clause . " and $column_name is not null ";
        }
      }
      #------------
      # TEXT FIELDS
      #------------
      else {  
        if( $op == 'is_blank' || $op == 'is_not_blank' ) 
          $display_value = '';
        else
          $display_value = " '" . $this->strip_all_slashes( $column_value ) . "'";

        $criteria_desc[] = $column_label . ' ' . $this->db_get_query_op_description( $op ) . $display_value;

        $query_value = trim( $this->escape( $column_value ));
        $sql_op = $this->db_get_sql_query_op( $op );

        if( $op == 'contains' || $op == 'does_not_contain' )
          $query_value = '%' . $query_value . '%';
        elseif( $op == 'starts_with' || $op == 'does_not_start_with' )
          $query_value = $query_value . '%';
        elseif( $op == 'ends_with' || $op == 'does_not_end_with' )
          $query_value = '%' . $query_value;
        elseif( $op == 'is_blank' )
          $sql_op = ' = ';
        elseif( $op == 'is_not_blank' )
          $sql_op = ' != ';

        $where_clause = $where_clause . " and coalesce(lower(trim( $column_name )),'') $sql_op '" 
                      . strtolower(trim( $query_value )) . "' ";
      }

      # Keep a record of criteria entered, so you can save the query if necessary
      $criterion = NULL;
      $criterion = array( 'column_name'  => $column_name,
                          'column_value' => $column_value,
                          'column_value2'=> $column_value2,
                          'op_name'      => $op_field,
                          'op_value'     => $op );
      $this->savable_criteria[] = $criterion; # Not filtered here, but an 'escape' is done before insert into DB
    }

    $this->selection_desc = 'Selection:';
    if( count( $criteria_desc ) < 1 )
      $this->selection_desc .= ' all.';
    else {
      for( $i = 0; $i < count( $criteria_desc ); $i++ ) {
        if( ! $this->is_ok_free_text( $criteria_desc[ $i ] )) die();
        if( $i > 0 ) $this->selection_desc .= '.';
        $this->selection_desc .= ' ' . $criteria_desc[ $i ];
      }
      $this->selection_desc .= '.';
    }

    if( ! $this->csv_output && ! $this->failed_validation ) {
      echo 'Selection: ';
      if( count( $criteria_desc ) < 1 )
        echo 'all.';
      elseif( count( $criteria_desc ) == 1 )
        echo $this->safe_output( $criteria_desc[ 0 ] );
      else {
        foreach( $criteria_desc as $criterion_desc ) {
          echo LINEBREAK;
          echo SPACE;
          html::bullet_point();
          echo $this->safe_output( $criterion_desc );
        }
      }
      echo LINEBREAK;

      if( $order_descrip ) {
        echo 'Data is sorted by: ' . $order_descrip;
        if( $sort_descending ) echo ' (descending order)';
        echo LINEBREAK;
        if( $this->publicly_available_page ) html::new_paragraph();
      }
      html::horizontal_rule();
    }

    
    $this->reading_selection_criteria = FALSE; # finished entering selection criteria
    return $where_clause;
  }
  #-----------------------------------------------------
  # In order to use this function you MUST be able to pass in the name of a unique, numeric key column

  function db_select_one_page_into_array( $class_to_call, $method_to_call,
                                          $selection_cols, $keycol, $from_tables, 
                                          $where_clause = '1=1', $order_by = '1', 
                                          $entries_per_page = DEFAULT_ENTRIES_PER_BROWSE_PAGE,
                                          $sort_descending  = DEFAULT_DESCENDING ) {

    $this->db_page_class_to_call   = $class_to_call;
    $this->db_page_method_to_call  = $method_to_call;
    $this->db_page_selection_cols  = $selection_cols;
    $this->db_page_keycol          = $keycol;
    $this->db_page_from_tables     = $from_tables;
    $this->db_page_where_clause    = $where_clause;
    $this->db_page_order_by        = $order_by;
    $this->db_page_sort_descending = $sort_descending;
    $this->db_page_entries_per_page= $entries_per_page;
    $this->db_page_total_pages     = 0;
    $this->db_page_required        = 0;

    $this->db_one_page_of_results = NULL;
    $rows = 0;

    if( ! $this->csv_output && ! $this->printable_output ) { # no need to waste time dividing results up into pages

      $statement = "select $keycol from $from_tables where $where_clause order by $order_by";
      if( $sort_descending ) $statement = $statement . ' desc';
      if( $order_by != $keycol ) $statement = $statement . ', ' . $keycol; # create a predictable order
      $keyarr = $this->db_select_into_array( $statement );

      if( ! is_array( $keyarr )) return NULL;

      $rows = count( $keyarr );
    }

    if( $this->csv_output || $this->printable_output || $rows <= $entries_per_page ) {  # return the whole array

      $statement = "select $selection_cols from $from_tables where $where_clause order by $order_by";
      if( $sort_descending ) $statement = $statement . ' desc';
      if( $order_by != $keycol ) {
        $statement = $statement . ', ' . $keycol; # create a predictable order
        if( $sort_descending ) $statement = $statement . ' desc';
      }

      $this->db_one_page_of_results = $this->db_select_into_array( $statement );

      if( ! $this->csv_output && ! $this->printable_output ) {

        $this->db_page_total_pages = 1;

        # External users cannot save their queries (no write privileges on database).
        # And we will not offer options which open new tabs/windows when in search popups (confusing for user).

        # The calling class may already be suppressing some of these options via db_set_search_result_parms(),
        # e.g. see ofp/official_paper.php.

        if( $this->publicly_available_page || $this->menu_called_as_popup || ! $this->menu_item_id ) {
          $this->suppress_save_query_button = TRUE;
        }
        elseif( $this->menu_called_as_popup ) {
          $this->suppress_csv_version_button = TRUE;
          $this->suppress_printable_version_button = TRUE;
        }

        if ( $rows == 1 )
          echo $rows . ' record found. ';
        else
          echo $rows . ' records found. ';
        echo LINEBREAK;

        html::div_start( 'class="pagination"');

        if( ! $this->suppress_printable_version_button ) {
          $this->db_write_printable_version_button();
        }

        if( ! $this->suppress_csv_version_button ) {
          $this->db_write_csv_version_button();
        }

        if( ! $this->suppress_save_query_button ) {
          $this->db_write_save_query_button();
        }

        if( ! $this->suppress_refresh_results_button ) {
          if(( $this->edit_method && $this->edit_tab == '_blank' ) || count( $_GET ) > 0 ) {
            $this->db_write_refresh_results_button();
          }
        }

        $this->db_write_custom_page_button();  # normally does nothing, but can be overridden by child class

        echo LINEBREAK;
        html::div_end( 'pagination');
      }

      $results = $this->db_one_page_of_results;

      if( $this->printable_output ) {  # you haven't displayed rowcount yet, do it now.
        $rows = count( $results );
        if ( $rows == 1 )
          echo $rows . ' record found.';
        else
          echo $rows . ' records found.';
        html::new_paragraph();
      }
      return $results;

    } #------ end of "return whole array"-----------#

    else {  # Query retrieves more than one page's worth of data
      $total_pages = ceil( intval($rows) / intval($entries_per_page));
      $page_required = $this->read_post_parm( 'page_required' );
      if( ! $page_required ) $page_required = 1;

      $last_match_on_page  = $page_required * $entries_per_page;
      if( $last_match_on_page > $rows ) $last_match_on_page = $rows;

      $first_match_on_page = (($page_required - 1) * $entries_per_page) + 1;

      if( ! $this->publicly_available_page ) html::italic_start();
      echo "Found $rows records ($total_pages pages)."
           . " Now displaying records $first_match_on_page to $last_match_on_page.";
      if( ! $this->publicly_available_page ) html::italic_end();

      $keyvals = '';
      for( $i = $first_match_on_page - 1; $i < $last_match_on_page; $i++ ) {
        $row = $keyarr[ $i ];
        if( $keyvals ) $keyvals = $keyvals . ', ';
        $keyvals = $keyvals . $row["$keycol"];
      }

      $statement = "select $selection_cols from $from_tables "
                 . " where $keycol in ( $keyvals ) "
                 . " order by $order_by";
      if( $sort_descending ) $statement = $statement . ' desc';
      if( $order_by != $keycol ) {
        $statement = $statement . ', ' . $keycol; # create a predictable order
        if( $sort_descending ) $statement = $statement . ' desc';
      }
      $this->db_one_page_of_results = $this->db_select_into_array( $statement );

      $keyarr = NULL;
      $this->db_page_required = $page_required;
      $this->db_page_total_pages = $total_pages;

      $this->db_write_page_selection_buttons();

      return $this->db_one_page_of_results;
    }
  }
  #-----------------------------------------------------

  ###############################################################################
  # You need to run db_select_one_page_into_array() before calling this method.
  ###############################################################################

  function db_write_page_selection_buttons() {

    if( ! $this->db_page_required ) return;

    if( $this->db_page_total_pages < 2 ) return;

    if( ! $this->db_page_class_to_call )  $this->die_on_error( 'Missing value "class to call" in dbentity' );
    if( ! $this->db_page_method_to_call ) $this->die_on_error( 'Missing value "method to call" in dbentity' );
    if( ! $this->db_page_selection_cols ) $this->die_on_error( 'Missing value "selection cols" in dbentity' );
    if( ! $this->db_page_from_tables )    $this->die_on_error( 'Missing value "from tables" in dbentity' );
    if( ! $this->db_page_where_clause )   $this->die_on_error( 'Missing value "where clause" in dbentity' );
    if( ! $this->db_page_order_by )       $this->die_on_error( 'Missing value "order by" in dbentity' );

    html::new_paragraph();

    html::div_start( 'class="pagination"');

    #--- "Next page" and "Prev page" buttons----------
    if( $this->db_page_required < $this->db_page_total_pages ) {
      html::form_start( $this->db_page_class_to_call, $this->db_page_method_to_call );
      $this->db_write_pagination_fields( $this->db_page_required + 1 );
      html::submit_button( 'go_to_' . strval( intval($this->db_page_required) + 1), 'Next page', 
                           $tabindex=1, ' class="pagelist" ' );
      html::form_end();
    }
    else
      html::button( 'no_next_page_button',  '[On last page]', 1, 'class="dummypage"' );

    if( $this->db_page_required > 1 ) {
      html::form_start( $this->db_page_class_to_call, $this->db_page_method_to_call );
      $this->db_write_pagination_fields( $this->db_page_required - 1 );
      html::submit_button( 'go_to_' . strval( intval($this->db_page_required) - 1), 'Prev page', 
                           $tabindex=1, ' class="pagelist" ' );
      html::form_end();
    }
    else
      html::button( 'no_prev_page_button',  '[On first page]', 1, 'class="dummypage"' );

    # External users cannot save their queries (no write privileges on database).
    # And we will not offer options which open new tabs/windows when in search popups (confusing for user).

    # The calling class may already be suppressing some of these options via db_set_search_result_parms(),
    # e.g. see ofp/official_paper.php.

    if( $this->publicly_available_page || $this->menu_called_as_popup || ! $this->menu_item_id ) {
      $this->suppress_save_query_button = TRUE;
    }
    elseif( $this->menu_called_as_popup ) {
      $this->suppress_csv_version_button = TRUE;
      $this->suppress_printable_version_button = TRUE;
    }

    if( ! $this->suppress_printable_version_button ) {
      $this->db_write_printable_version_button();
    }

    if( ! $this->suppress_csv_version_button ) {
      $this->db_write_csv_version_button();
    }

    if( ! $this->suppress_save_query_button ) {
      $this->db_write_save_query_button();
    }

    if( ! $this->suppress_refresh_results_button ) {
      if(( $this->edit_method && $this->edit_tab == '_blank' ) || count( $_GET ) > 0 ) {
        $this->db_write_refresh_results_button();
      }
    }

    $this->db_write_custom_page_button();  # normally does nothing, but can be overridden by child class

    echo LINEBREAK;

    #--- End of "Next page" and "Prev page" buttons----------

    html::new_paragraph();

    #--- List pages of data available ---

    if( $this->publicly_available_page || $this->menu_called_as_popup )
      $maxbuttons = DEFAULT_PAGE_SELECTION_BUTTONS_FOR_NARROW_PAGE;
    else
      $maxbuttons = DEFAULT_PAGE_SELECTION_BUTTONS;
    $buttons = 0;

    #--- Don't display hundreds and hundreds of page selection buttons
    $page_required = $this->db_page_required;
    $total_pages = $this->db_page_total_pages;
    $total_page_selection_buttons = $maxbuttons * MAX_PAGE_SELECTION_BUTTON_ROWS;

    if( $total_pages <= $total_page_selection_buttons ) {  # Can show full set of page selection buttons
      $first_page_to_show = 1;
      $last_page_to_show = $total_pages;
    }

    else {  # Need to show just a suitable subset of the buttons

      if( $page_required <= $total_page_selection_buttons - 1 ) {    # Lose some of the pages from the end
        $first_page_to_show = 1;
        $last_page_to_show = $total_page_selection_buttons;
      }

      else {                                                         # Lose some pages from start and/or end
        $first_page_to_show = floor($page_required/$total_page_selection_buttons) * $total_page_selection_buttons;
        $first_page_to_show--;

        $last_page_to_show = $first_page_to_show + $total_page_selection_buttons + 1;
        if( $last_page_to_show > $total_pages ) $last_page_to_show = $total_pages;
      }
    }

    for( $i = $first_page_to_show; $i <= $last_page_to_show; $i++ ) {
      if( $buttons > 0 && $buttons % $maxbuttons == 0 ) echo LINEBREAK;

      html::form_start( $this->db_page_class_to_call, $this->db_page_method_to_call );

      $page_to_get = $i;

      $this->db_write_pagination_fields( $page_to_get );

      if( $page_to_get == $first_page_to_show && $first_page_to_show > 1 )
        $button_name = '1 to ' . $page_to_get;
      elseif( $page_to_get == $last_page_to_show && $last_page_to_show < $total_pages )
        $button_name = $page_to_get . ' to ' . $total_pages;
      else
        $button_name = 'Page ' . $page_to_get;

      if( $this->db_page_required == $page_to_get ) # highlight the current page
        $button_class = 'currpage';
      else
        $button_class = 'pagelist';

      html::submit_button( 'go_to_' . $page_to_get, 
                           $button_name, 
                           $tabindex=1, 
                           ' class="' . $button_class . '" ' );
      html::form_end();
      $buttons++;
    }

    echo LINEBREAK;
    html::div_end( 'pagination' );
    html::new_paragraph();
  }
  #-----------------------------------------------------

  function db_write_csv_version_button( $button_name = 'Spreadsheet output' ) {

    html::form_start( $this->db_page_class_to_call, $this->db_page_method_to_call, NULL, $form_target = '_blank' );

    html::hidden_field( 'csv_output', 'Y' );

    $this->db_write_pagination_fields( $page_required = 1 );  # just use a dummy page required

    html::submit_button( 'csv_button', $button_name, $tabindex=1, ' class="pagelist" ' );

    html::form_end();
  }
  #-----------------------------------------------------

  function db_write_printable_version_button() {

    html::form_start( $this->db_page_class_to_call, $this->db_page_method_to_call, NULL, $form_target = '_blank' );

    html::hidden_field( 'printable_output', 'Y' );

    $this->db_write_pagination_fields();

    html::submit_button( 'printable_output_button', 'Printable output', $tabindex=1, ' class="pagelist" ' );

    html::form_end();
  }
  #-----------------------------------------------------

  function db_write_refresh_results_button() {

    if( $this->publicly_available_page && count( $_GET ) > 0 ) {
      echo LINEBREAK;
      echo 'The data currently being displayed could possibly be out of date, as this may be a cached page.'
           . ' Click Refresh Page to be sure the latest data is being displayed.';
    }

    html::form_start( $this->db_page_class_to_call, $this->db_page_method_to_call, NULL, $form_target = '_self' );

    $page_required = $this->db_page_required;  # current page or page 1 if we only have one page
    if( ! $page_required ) $page_required = 1;
    $this->db_write_pagination_fields( $page_required );

    html::submit_button( 'refresh_results_button', 'Refresh page', $tabindex=1, ' class="pagelist" ' );

    html::form_end();
  }
  #-----------------------------------------------------
  function db_write_custom_page_button() {  # normally does nothing, but can be overridden by child class
  }
  #-----------------------------------------------------

  function db_write_save_query_button() {

    html::form_start( $this->db_page_class_to_call, $this->db_page_method_to_call, NULL, $form_target = '_blank' );

    html::hidden_field( 'save_query', 'Y' );

    $this->db_write_pagination_fields();

    html::submit_button( 'save_query_button', 'Save query', $tabindex=1, ' class="pagelist" ' );

    html::form_end();
  }
  #-----------------------------------------------------

  function db_write_new_query_fields() {

    $this->db_write_hidden_selection_criteria();

    html::hidden_field( 'simplified_search', $this->simplified_search );

    if( $this->record_layout )
      html::hidden_field( 'record_layout', $this->record_layout );
    else
      html::hidden_field( 'record_layout', DEFAULT_RECORD_LAYOUT );

    if( $this->entries_per_page )
      html::hidden_field( 'entries_per_page', $this->entries_per_page );
    else
      html::hidden_field( 'entries_per_page', DEFAULT_ENTRIES_PER_BROWSE_PAGE );

    if( $this->order_by )
      html::hidden_field( 'order_by', $this->order_by );
    else
      html::hidden_field( 'order_by', '1' );

    if( $this->sort_descending )
      html::hidden_field( 'sort_descending', $this->sort_descending );

    if( $this->calling_form && $this->calling_field ) {
      html::hidden_field( 'calling_form', $this->calling_form );
      html::hidden_field( 'calling_field', $this->calling_field );
    }
  }
  #-----------------------------------------------------

  function db_write_pagination_fields( $page_required ) {

    html::hidden_field( 'page_required', $page_required );

    # Record the main elements of the query, so that query can be re-run to bring up next page.
    html::hidden_field( 'selection_cols', $this->db_page_selection_cols );

    #  Not sure why 'from tables' was ever written as hidden field!
    # (Value is set in db search result parms.)
    # html::hidden_field( 'from_tables', $this->db_page_from_tables );

    html::hidden_field( 'order_by',       $this->db_page_order_by );
    html::hidden_field( 'sort_descending',$this->db_page_sort_descending );

    # Selection desc has already been checked for malicious scripting, but let's double-check.
    # (Method hidden_field() will also do an htmlentities() on the value before writing it out.)
    if( ! $this->is_ok_free_text( $this->selection_desc )) die();
    html::hidden_field( 'selection_desc', $this->escape( $this->selection_desc ));

    $this->db_write_hidden_selection_criteria();

    html::hidden_field( 'simplified_search', $this->simplified_search );
    if( $this->parm_found_in_post( 'manual_search' )) html::hidden_field( 'manual_search', 'Y' );

    if( $this->record_layout )
      html::hidden_field( 'record_layout', $this->record_layout );
    else
      html::hidden_field( 'record_layout', DEFAULT_RECORD_LAYOUT );

    if( $this->entries_per_page )
      html::hidden_field( 'entries_per_page', $this->entries_per_page );
    else
      html::hidden_field( 'entries_per_page', DEFAULT_ENTRIES_PER_BROWSE_PAGE );

    if( $this->calling_form && $this->calling_field ) {
      html::hidden_field( 'calling_form', $this->calling_form );
      html::hidden_field( 'calling_field', $this->calling_field );
    }
  }
  #-----------------------------------------------------

  function db_write_hidden_selection_criteria() {

    # Make sure you look at the right set of columns: queryable columns or displayed columns.
    $keep_orig = $this->reading_selection_criteria;
    $this->reading_selection_criteria = TRUE;  # use this property to force all queried columns to be read

    $columns = $this->db_list_columns( $this->from_table );
    if( ! is_array( $columns )) return;

    $this->continue_on_read_parm_err = TRUE;
    $this->suppress_read_parm_errmsgs = TRUE;

    foreach( $columns as $row ) {

      extract( $row, EXTR_OVERWRITE ); # put row into variables

      if( ! $searchable ) continue;
      $this->$column_name = $this->read_post_parm( $column_name );

      $column_name2 = $column_name . '2';
      $this->$column_name2 = NULL;
      if( $is_date && $this->parm_found_in_post( $column_name2 ))
        $this->$column_name2 = $this->read_post_parm( $column_name2 );

      if( $is_date || $is_numeric )
        $op_field = 'date_or_numeric_query_op_' . $column_name; 
      else
        $op_field = 'text_query_op_' . $column_name; 

      $op = '';
      if( $this->parm_found_in_post( $op_field ))
        $op = $this->read_post_parm( $op_field );

      if( strval( $this->$column_name ) > '' 
      ||  strval( $this->$column_name2 ) > '' 
      ||  $op == 'is_blank' || $op == 'is_not_blank' ) {
        html::hidden_field( $column_name, $this->$column_name );
        html::hidden_field( $column_name2, $this->$column_name2 );
        html::hidden_field( $op_field, $op );
      }
    }

    $this->reading_selection_criteria = $keep_orig;  # omit non-displayable columns from display, even if queried on
  }
  #-----------------------------------------------------
  # This method is just left in for historical reasons, 
  # as it is still called by "scd", "modpol" etc (16 Jul 08),
  # but it does not have the "db_" prefix which is now standard.
  # Once references to it have been removed, can delete this method.
  # Replace with a direct call to db_produce_csv_output.

  function produce_csv_output( $rows = NULL ) {
    $this->db_produce_csv_output( $rows );
  }
  #-----------------------------------------------------

  function db_pre_filter_csv_output( &$rows, $convert_dates = TRUE ) {  # Note that rows is passed by reference
                                                                        # to save memory.

    if( $convert_dates ) {
      $rowcount = count($rows);

      # Identify date columns
      $statement = 'select a.attname from pg_attribute a, pg_type t where a.attrelid = '
                 . " (select oid from pg_class where relname='$this->from_table') "
                 . ' and a.attnum > 0 and a.atttypid = t.oid'
                 . " and t.typname in ( 'date', 'timestamp', 'timestamptz', 'time', 'timetz' )";

      $atts = $this->db_select_into_array( $statement );

      if( is_array( $atts )) {
        foreach( $atts as $row ) {
          $column_name = $row[ 'attname' ];

           for( $i = 0; $i < $rowcount; $i++ ) {
             $data_value = $rows[ $i ][ "$column_name" ];
             if( $this->is_postgres_timestamp( $data_value, FALSE )) {
               $data_value = $this->postgres_date_to_dd_mm_yyyy( $data_value );
               $rows[$i][ "$column_name" ] = $data_value;
             }
          }
        }
      }
    }
    return;
  }
  #-----------------------------------------------------

  function db_set_max_csv_rows_for_public_user() {  # can be overridden by child class

    $this->max_csv_rows_for_public_user = MAX_CSV_ROWS_FOR_PUBLIC_USER;

    if( $this->max_csv_rows_for_public_user > MAX_ROWS_IN_CSV_FILE )  
      $this->max_csv_rows_for_public_user = MAX_ROWS_IN_CSV_FILE;
  }
  #-----------------------------------------------------

  function db_produce_csv_output( &$rows,                # Note that rows is passed by reference to save memory
                                  $msg_recipient = NULL, # by default send file to self
                                  $msg_body = NULL,
                                  $msg_subject = NULL,
                                  $filename_root = 'QueryResults',
                                  $suppress_confirmation = FALSE ) {

    flush();

    if( ! is_array( $rows )) {
      echo 'No matches found.';
      return;
    }

    $this->db_set_max_csv_rows_for_public_user();  # this can be overridden by child class

    # However, set an absolute maximum which can't be overridden.
    if( $this->max_csv_rows_for_public_user > MAX_ROWS_IN_CSV_FILE )  
      $this->max_csv_rows_for_public_user = MAX_ROWS_IN_CSV_FILE;

    # We cannot allow the public to try and produce too large a file, as we handle large files by splitting
    # them into several sections and writing them into the report outputs table. But of course the general
    # public can't write to any table in our database.
    # Also data owners have concerns about outside users taking the whole database and in effect stealing it.

    if( ! $this->username && count( $rows ) > $this->max_csv_rows_for_public_user ) {
      echo 'You have requested too large a file. We can send you output files of up to ' 
           . $this->max_csv_rows_for_public_user 
           . ' lines per file. Please restrict your query so that it retrieves a smaller volume of data.';
      html::new_paragraph();
      html::button( 'cancel_csv_output', 'Close', $tabindex=1, 'onclick="self.close()"' );
      return;
    }

    #-----------------------------------------------------------------------------------------------
    # If not a logged-in user, or if no email address entered, get them to enter their email address
    #-----------------------------------------------------------------------------------------------
    elseif( ! $this->username || ! $this->read_session_parm( 'user_email' )) {

      if( ! $msg_recipient ) # Some batch jobs may pass in message recipient as function parameter,
                             # e.g. Collections management database for archives and manuscripts
        $msg_recipient = $this->read_post_parm( 'msg_recipient' );

      if( trim( $msg_recipient ) == '' ) {

        html::form_start( $this->read_post_parm( 'class_name' ), $this->read_post_parm( 'method_name' ));

        echo 'Your current query results can be sent to you as an email attachment if you wish. ';
        echo 'In order to receive your results by email, please enter your email address and click Proceed. ';
        echo LINEBREAK;
        echo LINEBREAK;

        html::input_field( 'msg_recipient', 'Your email address', NULL, FALSE, 50 );
        html::new_paragraph();
        html::submit_button( 'csv_button', 'Proceed' );
        html::button( 'cancel_csv_output', 'Cancel', $tabindex=1, 'onclick="self.close()"' );
        html::new_paragraph();

        html::ulist_start();
        html::listitem( "The type of email attachment will be 'CSV', which stands for 'Comma separated variables', "
             . ' and is a type of file which can, for example, be opened in spreadsheet format by Excel.'); 

        html::listitem( 'Email addresses entered via this form will not be stored on our database, '
                        . ' and in fact will have to be re-entered each time '
                        . ' that you ask for a set of query results to be sent to you.' );

        if( $this->username && ! $this->read_session_parm( 'user_email' )) {
          html::listitem( 'You are currently logged in as a full user of the system, '
                        . ' which means you have the ability to save an email address for yourself on the database '
                        . " via the 'Edit your own details' option on the main menu. "
                        . ' Why not do so? It could save you a lot of typing, as you would not have to re-enter'
                        . ' your email address each time you wanted to receive some query results by email.');
        }
        
        html::listitem( 'Sorry, we cannot send messages to email addresses containing characters '
                        . ' other than a standard set. The characters accepted are '
                        . ' A to Z and a to z, 0 to 9, at-sign (@), full-stop (.), hyphen (-)'
                        . ' and the underline character (_).');
        html::ulist_end();

        foreach( $_POST as $parm_name => $parm_value ) {
          if( $parm_name == 'msg_recipient' ) continue;
          if( $parm_name == 'csv_button' ) continue;
          if( $parm_name == 'from_tables' ) continue; # Not sure why 'from tables' was ever written as hidden field.
                                                      # (Value is set in db search result parms.)

          if( ! $this->is_ok_free_text( $parm_value )) die(); 
          html::hidden_field( $parm_name, $parm_value ); # validate properly when you next read the form values
        }

        html::form_end();
        return;
      }
    }

    $is_large_file = FALSE;
    $rowcount = count( $rows );
    if( $rowcount > MAX_ROWS_IN_CSV_FILE ) $is_large_file = TRUE;
    $output_id = NULL;
    if( $is_large_file ) {
      $batch_count = ceil( $rowcount / MAX_ROWS_IN_CSV_FILE );
      html::h3_start();
      echo "You have requested a large report of $rowcount lines. ";
      html::new_paragraph();
      echo "Producing your output in $batch_count batches to try and avoid running out of memory...";
      html::new_paragraph();
      html::h3_end();
      flush();

      $output_id = uniqid( rand(), TRUE );
      $output_id_checked = FALSE;
      while( ! $output_id_checked ) {
        $duplicate = NULL;
        $statement = 'select output_id from ' . $this->db_report_outputs_tablename()
                   . " where output_id = '$output_id'";
        $duplicate = $this->db_select_one_value( $statement );
        if( $duplicate )
          $output_id = uniqid( rand(), TRUE );
        else
          $output_id_checked = TRUE;
      }
    }

    $this->db_pre_filter_csv_output( $rows );  # Pass rows by reference to "filter" method to save memory.

    $file_content = '';
    $line_of_file = '';
    $first_row = TRUE;
    $total_columns = 0;
    $msg_frequency = 100;

    for( $thisrow = 0; $thisrow < $rowcount; $thisrow++ ) {

      if( $first_row ) {  # write column header
        $first_row = FALSE;
        $total_columns = count( $rows[$thisrow] );
        $i = 0;
        foreach( $rows[ $thisrow ] as $column_name => $column_value ) {
          $i++;
          $column_label = $this->db_get_default_column_label( $column_name );

          # Need to introduce some NEWLINES if the line is going to be very long
          # otherwise Outlook (or Excel) mangles the file.
          $words = explode( ' ', $column_label, 3 );
          if( count( $words ) > 2 ) {
            $column_label = $words[0] . ' ' . $words[1] . ' ' . NEWLINE . $words[2];
          }

          $line_of_file = $line_of_file . $this->csv_field( $column_label );
          if( $i < $total_columns ) $line_of_file = $line_of_file . ',';
        }
        $line_of_file = $line_of_file . CARRIAGE_RETURN . NEWLINE;

        if( ! $is_large_file ) # build up the output in memory
          $file_content .= $line_of_file;
        else {
          # Build up the output in a database table to avoid running out of memory
          $statement = 'insert into ' . $this->db_report_outputs_tablename()
                     . ' ( output_id, line_number, line_text ) values ('
                     . "'$output_id', 0, '" . $this->escape( $line_of_file ) . "')";
          $this->db_run_query( $statement );
        }
      }

      # Finished writing column headers on first row. Now write out the data.
      $line_of_file = '';
      $line_number = $thisrow + 1;
      $i = 0;
      foreach( $rows[ $thisrow ] as $column_name => &$column_value ) {
        $i++;
        $line_of_file = $line_of_file . $this->csv_field( $column_value );
        if( $i < $total_columns ) $line_of_file = $line_of_file . ',';
      }
      $line_of_file = $line_of_file . CARRIAGE_RETURN . NEWLINE;

      if( ! $is_large_file ) # build up the output in memory
        $file_content .= $line_of_file;
      else {
        # Build up the output in a database table to avoid running out of memory
        $statement = 'insert into ' . $this->db_report_outputs_tablename()
                   . ' ( output_id, line_number, line_text ) values ('
                   . "'$output_id', $line_number, '" . $this->escape( $line_of_file ) . "')";
        $this->db_run_query( $statement );
      }
      unset( $rows[ $thisrow ] );  # try and free up some memory

      if( ! $suppress_confirmation ) {
        if( $rowcount > $msg_frequency ) {
          if( $line_number % $msg_frequency == 0 ) { # time for another message
            echo "Processed $line_number lines out of a total of $rowcount..." . LINEBREAK;
            $anchor_name = 'processed_line_' . $line_number;
            html::anchor( $anchor_name );
            $script = 'window.location.hash = "#' . $anchor_name . '"';
            html::write_javascript_function( $script );
            flush();
          }
        }
      }
    }

    unset( $rows );  # try and free up some memory
    html::new_paragraph();

    if( ! $msg_body ) {
      $msg_body = ' - Results are attached from your query on the ' . CFG_SYSTEM_TITLE . '.    '
                . CARRIAGE_RETURN . NEWLINE;
      if( $this->menu_item_name )
        $msg_body .= ' - Menu option was: ' . $this->menu_item_name . '.    ' . CARRIAGE_RETURN . NEWLINE;

      if( $this->selection_desc ) {

        # Selection desc has already been checked for malicious scripting, but let's double-check
        if( ! $this->is_ok_free_text( $this->selection_desc )) die();

        $msg_body .= ' - ' . $this->selection_desc . CARRIAGE_RETURN . NEWLINE;
      }
    }

    html::new_paragraph();

    if( $is_large_file ) {
      for( $startrow = 1; $startrow <= $rowcount; $startrow += MAX_ROWS_IN_CSV_FILE ) {
        flush();
        $endrow = $startrow + MAX_ROWS_IN_CSV_FILE - 1;
        $curr_batch = $endrow / MAX_ROWS_IN_CSV_FILE;
        $file_content = '';

        echo "Producing part $curr_batch of your output...";
        html::new_paragraph();

        $statement = 'select line_text from ' . $this->db_report_outputs_tablename()
                   . " where output_id = '$output_id' and ( line_number = 0 "
                   . " or line_number between $startrow and $endrow ) "
                   . ' order by line_number';
        $this->db_run_query( $statement );
        while( $this->db_fetch_next_row()) {
          $line_of_file = $this->db_fetch_next_col();
          $file_content .= $line_of_file;
        }
        if( $curr_batch == $batch_count && ! $suppress_confirmation )
          $print_confirmation_msg = TRUE;
        else
          $print_confirmation_msg = FALSE;

        $success = $this->send_plain_text_attachment( $file_content,
                                                      $msg_recipient, # by default send file to self
                                                      $msg_body,
                                                      $msg_subject,
                                                      $filename_root . $curr_batch . 'of' . $batch_count . '.csv',
                                                      $print_confirmation_msg );
      }

      # Delete the temporary output
      $statement = 'delete from ' . $this->db_report_outputs_tablename()
                 . " where output_id = '$output_id'";
      $this->db_run_query( $statement );
    }

    else { # file is small enough to do all in one go.
      $print_confirmation_msg = TRUE;
      if( $suppress_confirmation ) $print_confirmation_msg = FALSE;
      $success = $this->send_plain_text_attachment( $file_content,
                                                    $msg_recipient, # by default send file to self
                                                    $msg_body, 
                                                    $msg_subject,
                                                    $filename_root . '.csv',
                                                    $print_confirmation_msg );
    }

    html::new_paragraph();
    if( ! $suppress_confirmation ) {
      $anchor_name = 'processed_' . $line_number . '_lines_of_results';
      html::anchor( $anchor_name );
      $script = 'window.location.hash = "#' . $anchor_name . '"';
      html::write_javascript_function( $script );
      flush();
    }
    html::new_paragraph();
  }
  #-----------------------------------------------------

  function db_save_query() {

    # Make sure you look at the right set of columns: queryable columns or displayed columns.
    $keep_orig = $this->reading_selection_criteria;
    $this->reading_selection_criteria = TRUE;  # use this property to force all queried columns to be read

    html::h3_start();
    echo 'Saving your latest query...';
    html::h3_end();
    html::new_paragraph();

    $query_class = $this->app_get_class( $this );
    $query_method = $this->search_method;

    $columns = $this->db_list_columns( $this->from_table );
    $this->db_read_selection_criteria( $columns );
    $this->db_read_presentation_style();

    # Selection desc has already been checked for malicious scripting, but let's double-check
    if( ! $this->is_ok_free_text( $this->selection_desc )) die();
    $query_title = $this->selection_desc;

    foreach( $columns as $row ) {
      $column_name = $row['column_name'];
      if( $column_name == $this->order_by ) {
        $order_by_desc = $row['column_label'];
        break;
      }
    }

    $query_title .= ' Data is sorted by: ';
    $query_title .= $order_by_desc;
    if( $this->sort_descending ) $query_title .= ' (descending order)';
    $query_title .= '.';

    $query_title .= ' Record layout: ';
    if( $this->record_layout == 'across_page' )
      $query_title .= 'across page';
    else
      $query_title .= 'down page';
    $query_title .= '.';

    $query_title .= ' Entries per page: ' . $this->entries_per_page . '. ';

    $this->db_save_query2( $query_class,
                           $query_method,
                           $query_title,
                           $query_criteria         = $this->savable_criteria,
                           $query_order_by         = $this->order_by,
                           $query_sort_descending  = $this->sort_descending,
                           $query_record_layout    = $this->record_layout,
                           $query_entries_per_page = $this->entries_per_page );

    html::new_paragraph();

    html::form_start( 'report', 'saved_query_list' );

    html::h3_start();
    echo 'Query saved.';
    html::h3_end();
    html::new_paragraph();

    echo 'Show your list of saved queries: ';
    html::submit_button();

    html::new_paragraph();
    html::italic_start();
    echo 'You can also view and re-run your saved queries via the Reports menu.';
    html::new_paragraph();
    echo 'Note: your results from the current query are still being shown in your original tab/window.';
    html::italic_end();
    html::form_end( 'report', 'saved_query_list' );

    $this->reading_selection_criteria = $keep_orig;  # omit non-displayable columns from display, even if queried on
  }
  #-----------------------------------------------------

  function db_save_query2( $query_class,
                           $query_method,
                           $query_title,
                           $query_criteria,
                           $query_order_by,
                           $query_sort_descending,
                           $query_record_layout,
                           $query_entries_per_page ) {

    $saved_queries_tablename = $this->db_user_saved_queries_tablename();
    $saved_queries_sequence = $this->db_id_seq_name( $saved_queries_tablename );

    $this->db_run_query( 'BEGIN TRANSACTION' );

    $statement = "select nextval('$saved_queries_sequence'::regclass)";
    $query_id = $this->db_select_one_value( $statement );

    $statement = "insert into $saved_queries_tablename ("
                 . ' query_id, '
                 . ' query_class, '
                 . ' query_method, '
                 . ' query_title, '
                 . ' query_order_by, '
                 . ' query_sort_descending, '
                 . ' query_record_layout, '
                 . ' query_entries_per_page, '
                 . ' query_menu_item_name ) '

                 . ' values ( '

                 . " $query_id, "
                 . " '$query_class', "
                 . " '$query_method', "
                 . " '" . $this->escape( $query_title ) . "', "
                 . " '$query_order_by', "
                 . " $query_sort_descending, "
                 . " '$query_record_layout', "
                 . " $query_entries_per_page, "

                 . " '" . $this->escape( $this->menu_item_name ) . "' )";


    $this->db_run_query( $statement );

    foreach( $query_criteria as $row ) {
      extract( $row, EXTR_OVERWRITE );

      $statement = 'insert into ' . $this->db_user_saved_query_selection_tablename() . ' ('
                 . ' query_id, column_name, column_value, column_value2, op_name, op_value ) values ( '
                 . "$query_id, "
                 . "'$column_name', "
                 . "'" . $this->escape( $column_value ) . "', "
                 . "'" . $this->escape( $column_value2 ) . "', "
                 . "'$op_name', "
                 . "'$op_value' )";

      $this->db_run_query( $statement );
    }

    $this->db_run_query( 'COMMIT' );
  }
  #-----------------------------------------------------

  function db_browse_across_page( $details, $columns ) {

    $this->reading_selection_criteria = FALSE;              # finished assessing query by now
    $columns = $this->db_list_columns( $this->from_table ); # refresh list of displayed and omitted columns

    if( $this->printable_output ) {  # Even if browsing ACROSS page, normally arrange printable output DOWN the page
                                     # unless method db_set_search_result_parms() sets force_printing_across_page
      if( $this->publicly_available_page ) {
        html::new_paragraph();

        html::italic_start();
        echo '(Note: if you have any difficulty printing out more than one page of your results, '
             . ' and you are not already using Internet Explorer as your browser, it is possible that you may have '
             . ' more success if you switch to Internet Explorer.)';
        html::italic_end();
      }

      if( $this->force_printing_across_page ) 
        $div_class = 'printableacrosspage';
      else
        $div_class = 'printabledownpage';
    }
    else
      $div_class = 'queryresults';

    html::div_start( ' class="' . $div_class . '" ' );

    html::table_start();

    html::table_head_start();
    html::tablerow_start();
    foreach( $columns as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      html::column_header( $column_label );
    }
    html::tablerow_end();
    html::table_head_end();

    html::table_body_start();
    foreach( $details as $row ) {
      $this->current_row_of_data = $row;  # give child classes access to all the data in the row at any time

      extract( $row, EXTR_OVERWRITE );
      html::tablerow_start();

      foreach( $columns as $crow ) {
        extract( $crow, EXTR_OVERWRITE );

        if( $this->edit_method && $column_name == $this->keycol && ! $this->printable_output ) {
          html::tabledata_start( ' class="highlight2 bold" ' );
          $this->db_browse_plugin_1( $column_name, $$column_name );
          $class_name = $this->app_get_class( $this );

          html::form_start( $class_name, $this->edit_method, NULL, $this->edit_tab );
          html::hidden_field( $column_name, $$column_name );
          html::hidden_field( 'opening_class', $this->menu_class_name );
          html::hidden_field( 'opening_method', $this->menu_method_name );

          # Manipulate the data before displaying it if you need to.
          # You've already got the original ID value in a hidden field now.
          $$column_name = $this->db_browse_reformat_data( $column_name, $$column_name );

          echo $$column_name ;
          echo LINEBREAK;

          html::submit_button( 'edit_button', 'Edit' );
          html::form_end();
          $this->db_browse_plugin_1a( $column_name, $$column_name );
          html::tabledata_end();
        }
        else {
          html::tabledata_start();

          # Manipulate the data before displaying it if you need to.
          $$column_name = $this->db_browse_reformat_data( $column_name, $$column_name );

          $this->echo_safely_with_linebreaks( $$column_name );

          $this->db_pass_data_back_from_popup( $column_name );

          $this->db_browse_plugin_2( $column_name, $$column_name );
          html::tabledata_end();
        }
      }
      html::tablerow_end();
    }
    html::table_body_end();
    html::table_end();
    html::div_end( $div_class );
  }
  #-----------------------------------------------------

  function db_browse_down_page( $details, $columns ) {

    $this->reading_selection_criteria = FALSE;              # finished assessing query by now
    $columns = $this->db_list_columns( $this->from_table ); # refresh list of displayed and omitted columns

    $record_count = count($details);
    if( $record_count < 1 ) {
      echo 'No matches found.';
      return;
    }
    $curr_record = 0;

    if( ! $this->printable_output )
      $div_class = 'queryresults';
    else
      $div_class = 'printabledownpage';

    html::div_start( ' class="' . $div_class . '" ' );

    foreach( $details as $row ) {
      $this->current_row_of_data = $row;  # give child classes access to all the data in the row at any time

      html::new_paragraph();
      html::horizontal_rule();
      html::new_paragraph();

      $curr_record++;
      $next_record = $curr_record + 1;
      $prev_record = $curr_record - 1;

      if( ! $this->printable_output ) {
        html::anchor( 'record_' . $curr_record );

        echo "Record $curr_record of $record_count on this page. " . LINEBREAK;

        if( $curr_record < $record_count ) html::link( '#record_' . $next_record, 'Next record' );
        if( $curr_record > 1 && $curr_record < $record_count ) echo ' | ';
        if( $curr_record > 1 ) html::link( '#record_' . $prev_record, 'Previous record' );

        if( $curr_record > 1 || $curr_record < $record_count )
          echo ' | ';
        html::link_to_page_top();
        echo ' | ';
        html::link_to_page_bottom();

        html::new_paragraph();
        html::table_start();
      }
      else {  # printable output
        html::italic_start();
        echo "Record $curr_record of $record_count. " . LINEBREAK;
        html::italic_end();
        html::table_start();
      }

      extract( $row, EXTR_OVERWRITE );

      foreach( $columns as $crow ) {
        extract( $crow, EXTR_OVERWRITE );
        html::tablerow_start();

        if( $this->edit_method && $column_name == $this->keycol && ! $this->printable_output ) {
          html::tabledata_start( 'class="rightaligned highlight2"' );
          $class_name = $this->app_get_class( $this );
          html::form_start( $class_name, $this->edit_method, NULL, $this->edit_tab );
          html::hidden_field( 'opening_class', $this->menu_class_name );
          html::hidden_field( 'opening_method', $this->menu_method_name );
          html::submit_button( 'edit_button', 'Edit' );
          html::hidden_field( $column_name, $$column_name );
          html::form_end();
          $this->db_browse_plugin_1( $column_name, $$column_name );
          html::tabledata_end();

          html::tabledata_start( 'class="highlight2"' );
          echo $column_label . ' ' . $$column_name;
          html::tabledata_end();
        }
        else {
          html::tabledata_start();
          $this->echo_safely( $column_label );
          html::tabledata_end();

          html::tabledata_start();

          # Manipulate the data before displaying it if you need to.
          $$column_name = $this->db_browse_reformat_data( $column_name, $$column_name );

          $this->echo_safely_with_linebreaks( $$column_name );
          $this->db_browse_plugin_2( $column_name, $$column_name );
          html::tabledata_end();
        }

        html::tablerow_end();
      }
      html::table_end();
      html::new_paragraph();
    }

    html::div_end( $div_class );
  }
  #-----------------------------------------------------
  function db_get_fixed_where_clause() {  # Allows a where-clause to be overridden by child class
                                          # without letting users fiddle with the selection 
    return '1=1';
  }
  #-----------------------------------------------------

  function db_browse() {

    $this->suppress_save_query_button = TRUE; # could be overridden by child class in "set browse parms" if required

    $this->db_set_browse_parms();

    if( ! $this->from_table || ! $this->keycol ) $this->die_on_error( 'No table/view name set in DB browse.' );

    $class_name = $this->app_get_class( $this );

    #--------------------------------------
    # Offer chance to change sort order etc
    #--------------------------------------
    # Identify the columns which can be used in "order by" clause
    $columns = $this->db_list_columns( $this->from_table );
    $possible_order_by_cols = $this->db_get_possible_order_by_cols( $columns );

    $order_by = $this->read_post_parm( 'order_by' );
    if( ! $order_by ) {
      $i = 0;
      foreach( $possible_order_by_cols as $row ) {
        $i++;
        foreach( $row as $colnam => $colval ) {
          $o = $colnam;
        }

        if( $i == 1 || $o == DEFAULT_ORDER_BY_COL ) $order_by = $o;
        if( $order_by == DEFAULT_ORDER_BY_COL ) break;
      }
    }

    $this->sort_descending = $this->read_post_parm( 'sort_descending' );
    if( $this->sort_descending == '' ) $this->sort_descending = DEFAULT_DESCENDING;

    $this->record_layout = $this->read_post_parm( 'record_layout' );
    if( $this->record_layout == '' ) $this->record_layout = DEFAULT_RECORD_LAYOUT;

    if( ! $this->csv_output && ! $this->printable_output ) {
      html::form_start( $class_name, $this->browse_method );
      $this->db_choose_presentation_style( $possible_order_by_cols, $order_by ); # contains submit button
      html::form_end();
    }

    #----------------------------------------------------------------------------
    # If there is going to be more than one page of data, decide which you are on
    #----------------------------------------------------------------------------
    $details = $this->db_select_one_page_into_array( $class_to_call   = $class_name,
                                                     $method_to_call  = $this->browse_method,
                                                     $selection_cols  = $this->db_get_selection_cols(), 
                                                     $keycol          = $this->keycol, 
                                                     $from_tables     = $this->from_table,
                                                     $where_clause    = $this->db_get_fixed_where_clause(), 
                                                     $order_by        = $order_by,
                                                     $entries_per_page= $this->entries_per_page,
                                                     $sort_descending = $this->sort_descending  );

    if( ! is_array( $details )) {
      html::new_paragraph();
      echo 'No data found.';
      html::new_paragraph();
      return;
    }

    #-----------------
    # Display the data
    #-----------------
    if( $this->csv_output ) 
      $this->db_produce_csv_output( $details );

    elseif( $this->record_layout == 'across_page' 
    && ( $this->force_printing_across_page || ! $this->printable_output )) 
      $this->db_browse_across_page( $details, $columns );

    else
      $this->db_browse_down_page( $details, $columns );

    if( ! $this->csv_output && ! $this->printable_output ) 
      $this->db_write_page_selection_buttons();
  }
  #-----------------------------------------------------
  ######################################################
  # --- End of functions for paginated searching ---
  ######################################################

  function db_select_from_multiple_checkboxes( $lookup_table, $lookup_id_col, $lookup_display_col,
                                               $detail_table, $detail_id_col_1, $detail_id_col_2, 
                                               $order_by_col = NULL, $in_table = TRUE,
                                               $detail_id_col_1_is_text = FALSE ) {

    # The following properties should have been set in calling method:
    #  - Key values, e.g. if checking person roles, then property "$this->person_id" should have been set.
    #  - $this->field_name (e.g. "person_roles" )
    #  - $this->field_label (e.g. "Person roles")
    #  - $this->label_parm (if a bold label is required, e.g. if mandatory)
    #  - if trying to display unsaved data (retrieved from POST), $this->$field_name e.g. array of role IDs

    if( ! $lookup_table || ! $detail_table ) 
      $this->die_on_error( 'Missing table name(s) in method "db_select from multiple checkboxes".' );
    if( ! $lookup_id_col || ! $detail_id_col_1 || ! $detail_id_col_2 ) 
      $this->die_on_error( 'Missing ID column name(s) in method "db_select from multiple checkboxes".' );
    if( ! $lookup_display_col ) 
      $this->die_on_error( 'Missing display column name in method "db_select from multiple checkboxes".' ); 
    if( ! $this->field_name ) 
      $this->die_on_error( 'Missing field name in method "db_select from multiple checkboxes".' ); 

    if( ! $order_by_col ) $order_by_col = $lookup_display_col;

    if( $in_table )
      html::tabledata( $this->field_label, $this->label_parm );
    else
      echo $this->field_label . LINEBREAK;

    if( $in_table ) html::tabledata_start( 'class="highlight2" ' );

    echo 'Tick as many as apply:';
    html::new_paragraph();

    $statement = "select $lookup_id_col, $lookup_display_col from $lookup_table order by $order_by_col";
    $look = $this->db_select_into_array( $statement );
    if( ! is_array( $look )) {
      echo $this->field_label . LINEBREAK;
      echo 'No options found.';
      if( $in_table ) html::tabledata_end();
      return;
    }

    $details = NULL;
    if( $this->$detail_id_col_1 && ! $this->failed_validation ) {
      $detail_id_col_1_value = $this->$detail_id_col_1;
      if( $detail_id_col_1_is_text ) $detail_id_col_1_value = "'" . $detail_id_col_1_value . "'";
      $statement = "select $detail_id_col_2 from $detail_table where $detail_id_col_1=$detail_id_col_1_value";
      $details = $this->db_select_into_array( $statement );
    }
    else {
      $field_name = $this->field_name;
      $details = $this->$field_name;
    }

    $checkbox_instance = 0;
    foreach( $look as $row ) {
      $checkbox_instance++;
      $checked = FALSE;
      extract( $row, EXTR_OVERWRITE );
      if( is_array( $details )) {
        foreach( $details as $det ) {
          if( $this->failed_validation ) { # values from POST
            if( $$lookup_id_col == $det ) {
              $checked = TRUE;
              break;
            }
          }
          else {  # values from database, so in a 'row' nested array
            if( $$lookup_id_col == $det[ "$detail_id_col_2" ] ) {
              $checked = TRUE;
              break;
            }
          }
        }
      }
      html::checkbox( $this->field_name . '[]', $$lookup_display_col, $checked, $$lookup_id_col,
                      $in_table = FALSE, $tabindex = 1, $checkbox_instance );
      echo LINEBREAK;
    }

    if( $in_table ) html::tabledata_end();
  }
  #-----------------------------------------------------

  function db_select_from_dropdown( $table_name, $id_col, $display_col, $order_col = NULL, 
                                    $suppress_blank_row = FALSE, $blank_row_title = '' ) {

    # The following properties should have been set in calling method:
    #  - Existing selected value, e.g. if entering field "cataloguer", will check property "$this->cataloguer"
    #  - $this->field_name (e.g. "cataloguer" )
    #  - $this->field_label (e.g. "Cataloguer responsible")
    #  - $this->label_parm (if a bold label is required, e.g. if mandatory)
    #  - if trying to display unsaved data (retrieved from POST), $this->$field_name e.g. cataloguer ID 12.

    if( ! $table_name  ) $this->die_on_error( 'No table name passed to method "db_select from dropdown".' );
    if( ! $id_col      ) $this->die_on_error( 'No ID column name passed to method "db_select from dropdown".' );
    if( ! $display_col ) $this->die_on_error( 'No display column name passed to method "db_select from dropdown".' );

    if( ! $order_col ) $order_col = $display_col;

    $statement = "select $id_col, $display_col from $table_name order by $order_col";
    $results = $this->db_select_into_array( $statement );

    html::dropdown_start( $this->field_name, $this->field_label, $this->in_table, $script = NULL, $tabindex = 1,
                          $this->label_parm ); 

    $field_name = $this->field_name;

    if( ! $suppress_blank_row || ! is_array( $results ))
      html::dropdown_option( 'null', $blank_row_title, $this->$field_name );

    if( is_array( $results )) {
      foreach( $results as $row ) {
        html::dropdown_option( $row[ "$id_col" ], $row[ "$display_col" ], $this->$field_name );
      }
    }

    html::dropdown_end(); 
  }
  #-----------------------------------------------------

  function db_get_default_column_label( $column_name = NULL ) {

    $first_char = substr( $column_name, 0, 1 );
    if( $first_char >= 'A' && $first_char <= 'Z' ) {  # already correctly formatted
      $column_label = $column_name;
      return $column_label;
    }
    else
      return ucfirst( str_replace( '_', ' ', $column_name ));
  }
  #-----------------------------------------------------

  function db_saved_changes_msg_and_close_button( $msg = NULL ) {

    if( ! $msg ) { # use default message

      $msg = $this->get_datetime_now_in_words() . '. Changes have been saved. '
           . ' To return to your original tab/window, click the Close button.';

      $this->read_opening_class_and_method();

      if( $this->opening_class == $this->app_get_class( $this )
      &&  $this->string_contains_substring( $this->opening_method, 'search' )
      &&  $this->string_contains_substring( $this->opening_method, 'results' ))
        $msg .= LINEBREAK . LINEBREAK
             . "(To bring your search results up to date, click the 'Refresh page' button which can be found "
             . " next to the 'Printable output', 'CSV output' and 'Save query' buttons in your original tab.) ";
    }

    $this->msg_and_close_button( $msg );
  }
  #-----------------------------------------------------

  function db_pass_data_back_from_popup( $column_name ) {

    if( $this->app_popup_add_selectform ) { # Will normally be set by method "app_popup_search_results()"
                                            # derived from Application Entity.
      if( ! $this->printable_output && ! $this->csv_output ) {

        if( $column_name == $this->app_popup_get_field_for_select_button() ) {
          $this->app_popup_pass_value_back();   # write 'Select' button letting you pass data back from popup window
        }
      }
    }
    return;
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {

    switch( $parm_name ) {

      case 'order_by':  # "order by" clause of query
        return $this->is_comma_separated_alphanumeric( $this->parm_value, $allow_underscores = TRUE );

      case 'sort_descending':
        return $this->is_on_off_switch( $this->parm_value );

      case 'page_required':  # page of query results to go to
      case 'entries_per_page':
        return $this->is_integer( $this->parm_value );

      case 'record_layout':  # output style of query results
        if( $this->parm_value == 'across_page' || $this->parm_value == 'down_page' || $this->parm_value == '' )
          return TRUE;
        else
          return FALSE;

      case 'printable_output':
      case 'csv_output':
      case 'save_query':
      case 'simplified_search':
      case 'manual_search':

        if( $this->parm_value == '' || $this->parm_value == 'Y' )
          return TRUE;
        else
          return FALSE;

      case 'change_timestamp':
      case 'change_timestamp2':
      case 'creation_timestamp':
      case 'creation_timestamp2':
        if( $this->parm_value != trim( $this->parm_value )) { # e.g. trailing space - easily done! Don't penalise them!
          $this->parm_value = trim( $this->parm_value );
          if( $this->parm_found_in_post( $parm_name )) $this->write_post_parm( $parm_name, $this->parm_value );
        }

        if( $this->is_postgres_timestamp( $this->parm_value )
        ||  $this->is_timestamp_query( $this->parm_value )
        ||  $this->is_dd_mm_yyyy( $this->parm_value )
        ||  strtolower( $this->parm_value ) == 'today'
        ||  strtolower( $this->parm_value ) == 'now' 
        ||  $this->parm_value == '' )
          return TRUE;
        else
          return FALSE;

      case 'change_user':
      case 'creation_user':
        return $this->is_alphanumeric_or_blank( $this->parm_value, $allow_underscores = TRUE );

      case 'change_type':
        return $this->is_alphabetic_or_blank( $this->parm_value );

      case 'msg_recipient':
        return $this->is_email_address( $this->parm_value );

      default:
        if( substr( $parm_name, 0, strlen('date_or_numeric_query_op_')) == 'date_or_numeric_query_op_') {
          return $this->db_is_query_op( $this->parm_value );
        }
        elseif( substr( $parm_name, 0, strlen('text_query_op_')) == 'text_query_op_') {
          return $this->db_is_query_op( $this->parm_value );
        }

        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
