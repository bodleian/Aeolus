<?php
/*
 * PHP class for handling reports
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */

require_once 'dbentity.php';

class Report extends DBEntity {

  #------------
  # Properties 
  #------------

  #-----------------------------------------------------

  function Report( &$db_connection ) { 

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->DBEntity( $db_connection );
  }
  #-----------------------------------------------------

  function reports_menu( $report_group_code = NULL ) {

    if( ! $report_group_code ) 
      $report_group_code = $this->read_post_parm( 'report_group_code' );

    $statement = 'select rg.report_group_title, rg.report_group_order, '
               . ' rg.on_main_reports_menu, rg.report_group_code, '
               . ' r.* '
               . ' from ' . $this->db_report_groups_tablename() . ' rg, '
               . $this->db_reports_tablename() . ' r, ' 
               . $this->db_menu_tablename() . ' m '
               . ' where r.report_group_id = rg.report_group_id '
               . ' and r.menu_item_id = m.menu_item_id'
               . " and m.user_restriction = '$this->menu_user_restriction'";

    if( $report_group_code ) 
      $statement = $statement . " and rg.report_group_code = '$report_group_code' ";
    else
      $statement = $statement . ' and rg.on_main_reports_menu = 1 ';

    $statement = $statement . ' order by report_group_order, report_group_title, report_id';

    $reports = $this->db_select_into_array( $statement );
    $parm_reader = NULL;

    if( ! is_array( $reports )) {
      echo 'No reports found';
      if( $report_group_code ) echo " for report group code '$report_group_code'.";
      echo LINEBREAK;
    }
    else {

      $last_report_group_title = '';

      foreach( $reports as $row ) {
        $report_group_title = $row[ 'report_group_title' ];
        $report_code = $row[ 'report_code' ];
        $report_title = $row[ 'report_title' ];
        $class_name = $row[ 'class_name' ];
        $method_name = $row[ 'method_name' ];
        $parm_list = $row[ 'parm_list' ];
        $parm_title_string = $row[ 'parm_titles' ];
        $prompt_for_parms = $row[ 'prompt_for_parms' ];
        $default_parm_value_string = $row[ 'default_parm_values' ];
        $parm_methods_string = $row[ 'parm_methods' ];
        $has_csv_option = $row[ 'has_csv_option' ];
        $is_dummy_option = $row[ 'is_dummy_option' ];
        $report_help = $row[ 'report_help' ];

        if( $report_group_title != $last_report_group_title ) {
          if( $last_report_group_title > '' ) {
             html::table_end();
             html::div_end();
          }
          html::new_paragraph();
          if ( ! $report_group_code ) { # do a header for each group in the menu
            html::h4_start();
            echo $report_group_title;
            html::h4_end();
            html::new_paragraph();
          }
          html::div_start( ' class="reportsmenu" ' );
          html::table_start( ' summary="' . $report_group_title . '" class="widelyspacepadded" ' ); 
        }

        html::tablerow_start();

        html::tabledata( $report_title, ' class="bold" ' );

        html::tabledata_start();
        $form_name = NULL;

        #-------------------------------
        # Get parameters for this report
        #-------------------------------
        if( $class_name && $method_name ) {
          $form_target = '_blank';
          if( $is_dummy_option ) $form_target = '_self';
          $form_name = html::form_start( $class_name, $method_name, $class_name . '_' . $method_name, $form_target );

          if( $parm_title_string && ! $prompt_for_parms ) echo $parm_title_string;

          $parm_count = 0;
          $parm_name = trim( strtok( $parm_list, ',;' ));
          $parm_value = '';
          $parm_titles = explode( ',', $parm_title_string );
          $default_parm_values = explode( ',', $default_parm_value_string );
          $parm_methods = explode( ',', $parm_methods_string );

          while( $parm_name ) {

            #-----------------------------------------------------------------------
            # Create an object of the class to which the main report method belongs.
            # This will run its own "validate" method when reading parameters.
            #-----------------------------------------------------------------------
            $parm_reader_type = NULL;
            if( ! is_object( $parm_reader ))
              $parm_reader = new $class_name( $this->db_connection );
            else {
              $parm_reader_type = $this->app_get_class( $parm_reader );
              if( $parm_reader_type != strtolower( $class_name ))
                $parm_reader = new $class_name( $this->db_connection );
            }
            $parm_reader->form_name = $form_name; # make current form name available to called classes/methods
            $parm_value = $parm_reader->read_post_parm( $parm_name ); # validate parameters in class-specific way

            #----------------------------------------------------------------------------------------------
            # If "prompt for parms" is set, extract the parameter names, labels and default values if any.
            # If a "parm method" is found, use that method to enter the required parameter.
            # Otherwise use a simple input field.
            #----------------------------------------------------------------------------------------------
            if( $prompt_for_parms ) {
              $parm_title = '';
              $default_value = '';
              $parm_method = '';

              if( $parm_titles ) {
                if( is_array( $parm_titles ))
                  $parm_title = $parm_titles[ $parm_count ];
                else
                  $parm_title = $parm_titles;
                $parm_title = trim( $parm_title );
              }

              if( $default_parm_values ) {
                if( is_array( $default_parm_values ))
                  $default_value = $default_parm_values[ $parm_count ];
                else
                  $default_value = $default_parm_values;
                $default_value = trim( $default_value );
              }

              if( $parm_methods ) {
                if( is_array( $parm_methods ))
                  $parm_method = $parm_methods[ $parm_count ];
                else
                  $parm_method = $parm_methods;
                $parm_method = trim( $parm_method );
              }

              html::new_paragraph();
              if( $parm_method ) # method has to be on the same class as the main report method
                $parm_reader->$parm_method( $default_value );
              else
                html::input_field( $parm_name, $parm_title, $default_value );
            }

            #-----------------------------------------------------
            # If "prompt for parms" false, write out hidden fields
            #-----------------------------------------------------
            else
              html::hidden_field( $parm_name, $parm_value );

            $parm_count++;
            $parm_name = strtok( ',;' );
            $parm_name = trim( $parm_name );
          }

          if( $parm_list && $prompt_for_parms ) html::new_paragraph();

          html::hidden_field( 'menu_user_restriction', $this->menu_user_restriction );

          #-----------------------------------
          # Decide on printable vs. CSV output
          #-----------------------------------
          if( $is_dummy_option ) {
            html::hidden_field( 'printable_output', '' );
            html::hidden_field( 'csv_output', '' );
          }

          elseif( ! $has_csv_option ) {
            html::hidden_field( 'printable_output', 'Y' );
            html::hidden_field( 'csv_output', '' );
          }

          else {  # allow choice between printable and CSV output
            echo 'Output style: ';

            html::hidden_field( 'printable_output', 'Y' );
            html::hidden_field( 'csv_output', '' );

            for( $i = 0; $i <= 1; $i++ ) {
              if( $i == 0 ) {
                $this_type = 'printable';
                $label = 'Printable';
                $other_type = 'csv';
                $initial_value = 'Y';
              }
              else {
                $this_type = 'csv';
                $label = 'CSV';
                $other_type = 'printable';
                $initial_value = '';
              }

              $this_type_checked = $this_type . '_checked';

              $script =  'onClick="var ' . $this_type_checked . ';' . NEWLINE;
              $script .= "$this_type_checked = document.$form_name.${form_name}_output_type[ $i ].checked;" . NEWLINE;
              $script .= "if( $this_type_checked == true ) { " . NEWLINE  ;
              $script .= "  document.$form_name.${this_type}_output.value = 'Y';" . NEWLINE;
              $script .= "  document.$form_name.${other_type}_output.value = '';" . NEWLINE;
              $script .= '}' . NEWLINE;
              $script .= 'else { ' . NEWLINE  ;
              $script .= "  document.$form_name.${this_type}_output.value = '';" . NEWLINE;
              $script .= "  document.$form_name.${other_type}_output.value = 'Y';" . NEWLINE;
              $script .= '}' . '" ';

              html::radio_button( $fieldname = $form_name . '_output_type', 
                                  $label = $label, 
                                  $value_when_checked='Y', 
                                  $current_value = $initial_value, 
                                  $tabindex=1, 
                                  $button_instance=$i+1, 
                                  $script=$script );
            }
          }

          html::submit_button();
          html::form_end();
        }

        html::tabledata_end();
  
        if( $report_help ) {
          html::tabledata_start();
          echo $report_help ; # doing a simple echo like this, rather than "echo safely" will enable HTML tags
          html::tabledata_end();
        }

        html::tablerow_end();
        $last_report_group_title = $report_group_title;
      }
      html::table_end();
      html::div_end();
    }

    html::new_paragraph();
    html::horizontal_rule();
    html::new_paragraph();

    html::italic_start();
    echo 'A report will be produced in a new window/tab when you click on the "OK" button. ';
    echo 'You can then print the report by clicking File/Print. If you wish to print the report in Landscape '
         . ' rather than Portrait, choose File/Print Preview first. In Print Preview, the Page Setup button which '
         . ' allows you to change the paper orientation can be found near the top lefthand corner of the window.';
    html::italic_end();
    html::new_paragraph();

    html::bold_start();
    echo 'Column headers CAN be printed at the top of every page when you print out a paper copy of these reports.';
    html::bold_end();
    html::italic_start();
    echo ' Please note, however, that Internet Explorer does not currently seem to print out headings on every page. ';
    echo ' In order to get headings printed on every page, you will need to use Mozilla Firefox as your browser.';
    echo ' Please note also that even in Mozilla Firefox, the headings are only repeated on every page '
         . ' when PRINTING or in PRINT PREVIEW. They do not appear when in normal browse mode.';
    html::italic_end();
    html::new_paragraph();
  }
  #-----------------------------------------------------

  function saved_query_list() {

    $this->db_set_browse_parms();

    $statement = 'select count(*) from ' . $this->from_table . ' where ' . $this->db_get_fixed_where_clause();
    $queries_found = $this->db_select_one_value( $statement );
    if( $queries_found < 1 ) {
      html::h3_start();
      echo 'No saved queries found.';
      html::h3_end();
      html::new_paragraph();
      echo 'To save your queries and the data presentation options that you chose at the time'
           . " when you first ran them, click the 'Save query' button which appears near the top"
           . ' of most search results screens.';
      html::new_paragraph();
      echo 'The details of any queries you save in this way will then appear here,'
           . ' and you will be able to re-run your queries, amend them and re-save them if you wish.';
      return;
    }

    html::h3_start();
    echo "Click the 'Run' button beside a query to load that selection back into the search screen.";
    html::h3_end();
    html::new_paragraph();

    echo "You can change the query title to make it more meaningful if you wish. Click 'Edit' to change the title.";
    html::new_paragraph();

    echo "Click the 'Delete' button to remove a saved query from the list.";
    html::new_paragraph();

    if( ! $this->parm_found_in_post( 'order_by' )) {
      $this->write_post_parm( 'order_by', 'creation_timestamp' );
      $this->write_post_parm( 'sort_descending', 1 );
    }

    $this->db_browse();
  }
  #-----------------------------------------------------

  function db_set_browse_parms() {  # Overrides parent method from 'dbentity'
                                    # currently applies only to user saved queries
    $this->browse_method  = 'saved_query_list';
    $this->from_table     = $this->db_user_saved_queries_tablename();
    $this->keycol         = 'query_id';
    $this->edit_method    = 'edit_saved_query';

    $this->suppress_refresh_results_button = 'Y';
  }
  #-----------------------------------------------------

  function db_get_fixed_where_clause() {

    if( $this->from_table == $this->db_user_saved_queries_tablename() ) {
      $where_clause = " username = '" . $this->db_get_username() . "' and query_id > 0";
      return $where_clause;
    }
    else
      return parent::db_get_fixed_where_clause();  # normally 1=1
  }
  #-----------------------------------------------------

  function db_list_columns() {  

    $rawcols = parent::db_list_columns( $this->from_table );

    $cols = array();

    foreach( $rawcols as $row ) {
      extract( $row, EXTR_OVERWRITE );

      $include_in_display = FALSE;

      switch( $column_name ) {
        case 'query_id':
        case 'query_title':
        case 'query_menu_item_name':
        case 'creation_timestamp':
          $include_in_display = TRUE;
          break;

        default:
          break;
      }

      if( $include_in_display ) {

        switch( $column_name ) {
          case 'query_id':
            $row[ 'column_label' ] = 'Actions';
            break;

          case 'query_menu_item_name':
            $row[ 'column_label' ] = 'Menu item';
            break;

          case 'creation_timestamp':
            $row[ 'column_label' ] = 'Created';
            break;

          case 'query_title':   # OK as it is
            break;

          default:
            break;
        }
        $cols[] = $row;
      }
    }

    return $cols;
  }
  #-----------------------------------------------------

  function db_browse_reformat_data( $column_name = NULL, $column_value = NULL ) {  

    if( $column_name == 'query_id' ) {
      $this->hidden_query_id = $column_value;
      return NULL;
    }

    elseif( $column_name == 'creation_timestamp' )
      return $this->postgres_date_to_dd_mm_yyyy( $column_value );

    else
      return $column_value;
  }
  #-----------------------------------------------------

  function db_browse_plugin_1( $column_name, $column_value ) {
    if( $column_name == 'query_id' ) $this->write_run_query_button( $column_value );
  }
  #-----------------------------------------------------

  function db_browse_plugin_1a( $column_name, $column_value ) {
    if( $column_name == 'query_id' ) $this->write_delete_button( $this->hidden_query_id );
  }
  #-----------------------------------------------------

  function write_run_query_button( $query_id ) {

    extract( $this->current_row_of_data, EXTR_OVERWRITE );  # dbentity sets up $this->current_row_of_data

    html::form_start( $query_class, $query_method, NULL, '_self' );

    html::hidden_field( 'order_by',         $query_order_by );
    html::hidden_field( 'sort_descending',  $query_sort_descending );
    html::hidden_field( 'entries_per_page', $query_entries_per_page );
    html::hidden_field( 'record_layout',    $query_record_layout );

    $statement = 'select * from ' . $this->db_user_saved_query_selection_tablename()
               . ' where query_id = ' . $query_id;
    $criteria = $this->db_select_into_array( $statement );
    foreach( $criteria as $criterion ) {
      extract( $criterion, EXTR_OVERWRITE );
      html::hidden_field( $column_name, $this->escape( $column_value ));
      html::hidden_field( $column_name . '2', $this->escape( $column_value2 ));  # for queries on dates by range
      html::hidden_field( $op_name, $op_value );
    }

    html::submit_button( 'load_query_button', 'Run' );
    html::form_end();
  
  }
  #-----------------------------------------------------

  function write_delete_button( $query_id ) {

    echo LINEBREAK;
    html::form_start( 'report', 'delete_saved_query' );

    html::hidden_field( 'query_id', $query_id );

    html::submit_button( 'delete_query_button', 'Delete' );
    html::form_end();
    echo LINEBREAK;
  }
  #-----------------------------------------------------
  function edit_saved_query() {

    html::new_paragraph();
    html::form_start( 'report', 'edit_saved_query2' );
    html::new_paragraph();

    $query_id = $this->read_post_parm( 'query_id' );
    if( ! $query_id ) $this->die_on_error( 'No query ID passed to "edit saved query"' );

    html::hidden_field( 'query_id', $query_id );

    $query_title = $this->db_select_one_value( 'select query_title from ' . $this->db_user_saved_queries_tablename()
                                             . ' where query_id = ' . $query_id );

    html::input_field( 'query_title', 'Query title', $query_title, $in_table = FALSE, $size = 100 );
    html::new_paragraph();
    html::submit_button( 'save_button', 'Save' );
    html::submit_button( 'cancel_button', 'Cancel' );

    html::form_end();
  }
  #-----------------------------------------------------

  function edit_saved_query2() {

    if( $this->parm_found_in_post( 'save_button' )) {
      $query_id = $this->read_post_parm( 'query_id' );
      if( ! $query_id ) $this->die_on_error( 'No query ID passed to "edit saved query"' );
      $query_title = $this->read_post_parm( 'query_title' );
      $statement = 'update ' . $this->db_user_saved_queries_tablename() . ' set query_title = '
                 . "'" . $this->escape( $query_title ) . "' where query_id = $query_id";
      $this->db_run_query( $statement );
      echo 'Change saved.';
    }
    else
      echo 'Change cancelled.';

    html::new_paragraph();
    $this->saved_query_list();
  }
  #-----------------------------------------------------
  function delete_saved_query() {

    html::new_paragraph();
    html::form_start( 'report', 'delete_saved_query2' );
    html::new_paragraph();

    $query_id = $this->read_post_parm( 'query_id' );
    if( ! $query_id ) $this->die_on_error( 'No query ID passed to "delete saved query"' );

    html::hidden_field( 'query_id', $query_id );

    $query_title = $this->db_select_one_value( 'select query_title from ' . $this->db_user_saved_queries_tablename()
                                             . ' where query_id = ' . $query_id );

    echo 'Query title: ';
    $this->echo_safely( $query_title );

    html::new_paragraph();
    html::submit_button( 'delete_button', 'Delete' );
    html::submit_button( 'cancel_button', 'Cancel' );

    html::form_end();
  }
  #-----------------------------------------------------

  function delete_saved_query2() {

    if( $this->parm_found_in_post( 'delete_button' )) {
      $query_id = $this->read_post_parm( 'query_id' );
      if( ! $query_id ) $this->die_on_error( 'No query ID passed to "delete saved query"' );

      $this->db_run_query( 'BEGIN TRANSACTION' );

      $statement = 'delete from ' . $this->db_user_saved_query_selection_tablename() 
                 . " where query_id = $query_id";
      $this->db_run_query( $statement );

      $statement = 'delete from ' . $this->db_user_saved_queries_tablename() . " where query_id = $query_id";
      $this->db_run_query( $statement );

      $this->db_run_query( 'COMMIT' );

      echo 'Query deleted.';
    }
    else
      echo 'Deletion cancelled.';

    html::new_paragraph();
    $this->saved_query_list();
  }
  #-----------------------------------------------------
  function db_choose_presentation_style() {

    # Don't let them have all the normal report options, it would just cause confusion

    $this->order_by          = 'query_id';
    $this->sort_descending   = 1;
    $this->entries_per_page  = DEFAULT_ENTRIES_PER_BROWSE_PAGE;
    $this->record_layout     = DEFAULT_RECORD_LAYOUT;

    html::hidden_field( 'order_by',         $this->order_by );
    html::hidden_field( 'sort_descending',  $this->sort_descending );
    html::hidden_field( 'entries_per_page', $this->entries_per_page );
    html::hidden_field( 'record_layout',    $this->record_layout );
  }
  #-----------------------------------------------------
  function db_write_printable_version_button() {}  # suppress
  function db_write_csv_version_button() {}
  function db_write_save_query_button() {}
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'report_id':
      case 'query_id':
        return $this->is_integer( $this->parm_value );

      case 'report_code':
      case 'report_group_code':
        return $this->is_alphanumeric_or_blank( $this->parm_value, $allow_underscores = TRUE );

      case 'query_title':
        return $this->is_ok_free_text( $this->parm_value );
      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
