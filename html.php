<?php
/*
 * PHP class for writing HTML
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */

class HTML extends Application_Entity {
  #-----------------------------------------------------------------

  function html_start() {

    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" ';
    echo NEWLINE;
    echo '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
    echo NEWLINE;

    echo '<html>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------
  function html_end() {

    echo NEWLINE;
    echo '</html>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------
  function html_head_start() {

    echo NEWLINE;
    echo '<head>';
    echo NEWLINE;

    echo '<meta http-equiv="Content-Type" content="text/html; charset=' 
         . Application_Entity::get_character_encoding() . '">';
    echo NEWLINE;
    echo '<meta http-equiv="Content-Language" content="en-gb">';
    echo NEWLINE;

    $page_title = trim( CFG_SYSTEM_TITLE );
    if( CONSTANT_DATABASE_NAME != CONSTANT_LIVE_DATABASE_NAME )
      $page_title = $page_title . ': Test/Training DB';

    echo "<title>$page_title</title>" . NEWLINE;
  }
  #-----------------------------------------------------------------

  function call_htmlentities( $value, $quote_conversion = NULL, $charset = NULL ) {

    if( ! $quote_conversion ) $quote_conversion = ENT_QUOTES;
    if( ! $charset ) $charset = Application_Entity::get_character_encoding();

    return htmlentities( $value, $quote_conversion, $charset );
  }
  #-----------------------------------------------------------------

  function header_text_colour() {

    return OXFORD_BLUE ;
  }
  #-----------------------------------------------------------------

  function footer_text_colour() {

    return html::header_text_colour();
  }
  #-----------------------------------------------------------------

  function header_background_colour() {

    return html::get_highlight1_colour(); 
  }
  #-----------------------------------------------------------------

  function footer_background_colour() {

    return html::header_background_colour();
  }
  #-----------------------------------------------------------------

  function write_stylesheet( $for_cms_deployment = FALSE, $banner_only = FALSE, $printable = FALSE ) {

    echo '<style type="text/css">' . NEWLINE;

    #---------
    # Defaults
    #---------
    if( ! $for_cms_deployment ) {

      echo ' body, textarea {'                                        . NEWLINE;
      echo '   font-family: Verdana, Helvetica, Arial, sans-serif; '  . NEWLINE;
      echo '   font-size: 9pt;'                                       . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' body {'                                                  . NEWLINE;
      echo '   color: black;'                                         . NEWLINE;
      echo '   background:  white;'                                   . NEWLINE;
      echo '   width:  auto;'                                         . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' h1 {'                                                    . NEWLINE; 
      echo '   font-size: 20pt;'                                      . NEWLINE;
      echo '   font-weight: normal;'                                  . NEWLINE;
      echo '   margin-top: 20px; '                                    . NEWLINE;
      echo '   margin-bottom: 10px; '                                 . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' h2 {'                                                    . NEWLINE; 
      echo '   font-size: 18pt;'                                      . NEWLINE;
      echo '   font-weight: normal;'                                  . NEWLINE;
      echo '   margin-top: 10px; '                                    . NEWLINE;
      echo '   margin-bottom: 10px; '                                 . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' h3 {'                                                    . NEWLINE; 
      echo '   font-size: 14pt;'                                      . NEWLINE;
      echo '   font-weight: normal;'                                  . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' a:link  {'                                               . NEWLINE; 
      echo '   color: ' .  html::header_text_colour() . ';'           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' a:active, a:hover, a:visited  {'                                      . NEWLINE; 
      echo '   color: ' .  html::get_contrast2_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                    . NEWLINE;

      echo ' td {'                                                    . NEWLINE;
      echo '   vertical-align:top;'                                   . NEWLINE;
      echo '   text-align:left;'                                      . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' th {'                                                    . NEWLINE;
      echo '    vertical-align:bottom;'                               . NEWLINE;
      echo '    text-align:left;'                                     . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' form {'                                                  . NEWLINE; 
      echo '   margin-top: 0px; '                                     . NEWLINE;
      echo '   margin-bottom: 0px; '                                  . NEWLINE;
      echo '   padding: 2px; '                                        . NEWLINE;
      echo ' }'                                                       . NEWLINE;


      #--------
      # Banner
      #--------
      #-------------------
      # Normal page banner
      #-------------------
      echo ' .banner {'                                                                        . NEWLINE; 
      echo '   color: ' .  html::header_text_colour() . ';'                                    . NEWLINE;
      echo '   background-color: ' .  html::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                                . NEWLINE;

      echo ' .banner h1  {'                                           . NEWLINE; 
      echo '   color: ' .  html::header_text_colour() . ';'           . NEWLINE;
      echo '   margin-top: 0px; '                                     . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .banner h2 {'                                            . NEWLINE; 
      echo '   color: ' .  html::header_text_colour() . ';'           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .banner a:link, .banner a:visited  {'                    . NEWLINE; 
      echo '   font-weight: bold;'                                    . NEWLINE;
      echo '   text-decoration: none;'                                . NEWLINE;
      echo '   color: ' .  html::header_text_colour() . ';'           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .banner a:active, .banner a:hover  {'                                 . NEWLINE; 
      echo '   font-weight: bold;'                                                 . NEWLINE;
      echo '   text-decoration: none;'                                             . NEWLINE;
      echo '   color: ' .  html::get_contrast2_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                    . NEWLINE;

      echo ' .banner br {line-height: 15px ; clear: left}'            . NEWLINE;

      echo ' img.bannerlogo {'                                                             . NEWLINE;
      echo '   border-style: solid;'                                                       . NEWLINE;
      echo '   border-color: ' .  html::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo '   border-top-width: 0px;'                                                     . NEWLINE;
      echo '   border-left-width: 20px;'                                                   . NEWLINE;
      echo '   border-right-width: 20px;'                                                  . NEWLINE;
      echo '   float:left; clear:left; '                                                   . NEWLINE;
      echo ' }'                                                                            . NEWLINE;

      echo ' .banner form {'                                          . NEWLINE; 
      echo '   display: inline; '                                     . NEWLINE;
      echo '   padding: 0px; '                                        . NEWLINE;
      echo '   margin-left: 0px; '                                    . NEWLINE;
      echo '   margin-right: 0px; '                                   . NEWLINE;
      echo '   float:right; '                                         . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .banner input  {'                                                                 . NEWLINE;
      echo '   color: ' .  html::header_text_colour() . ';'                                    . NEWLINE;
      echo '   background-color: ' .  html::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo '   border-style: none;'                                                            . NEWLINE;
      echo '   border-width: 0px;'                                                             . NEWLINE;
      echo '   padding: 0px; '                                                                 . NEWLINE;
      echo ' }'                                                                                . NEWLINE;

      #----------------------------
      # Banner for printable output
      #----------------------------
      echo ' .printbanner h1  {'                                      . NEWLINE; 
      echo '   margin-top: 0px; '                                     . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .printbanner br {line-height: 15px ; clear: left}'       . NEWLINE;

      echo ' img.printbannerlogo {'                                   . NEWLINE;
      echo '   border-style: solid;'                                  . NEWLINE;
      echo '   border-color: white;'                                  . NEWLINE;
      echo '   border-top-width: 0px;'                                . NEWLINE;
      echo '   border-left-width: 20px;'                              . NEWLINE;
      echo '   border-right-width: 20px;'                             . NEWLINE;
      echo '   float:left; clear:left; '                              . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      if( $banner_only ) {
        echo '</style>' . NEWLINE;
        return;
      }

      #--------
      # Footer 
      #--------
      echo ' .footerlinks {'                                                                   . NEWLINE; 
      echo '   color: ' .  html::footer_text_colour() . ';'                                    . NEWLINE;
      echo '   background-color: ' .  html::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                                . NEWLINE;

      echo ' .footerlinks a:link, .footerlinks a:visited  {'          . NEWLINE; 
      echo '   font-weight: bold;'                                    . NEWLINE;
      echo '   text-decoration: none;'                                . NEWLINE;
      echo '   color: ' .  html::footer_text_colour() . ';'           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .footerlinks a:active, .footerlinks a:hover  {'                       . NEWLINE; 
      echo '   font-weight: bold;'                                                 . NEWLINE;
      echo '   text-decoration: none;'                                             . NEWLINE;
      echo '   color: ' .  html::get_contrast2_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                    . NEWLINE;

      echo ' .footnotes {'                                                                     . NEWLINE; 
      echo '   color: ' .  html::footer_text_colour() . ';'                                    . NEWLINE;
      echo '   background-color: ' .  html::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                                . NEWLINE;

      #-----------
      # Main menu
      #-----------
      echo '.mainmenu br {line-height: 15px ; clear: left}'           . NEWLINE;

      echo ' .mainmenu a:link {'                                      . NEWLINE;
      echo '   font-weight: bold;'                                    . NEWLINE;
      echo '   font-size: 11pt;'                                      . NEWLINE;
      echo '   text-decoration: none;'                                . NEWLINE;
      echo '   color: ' .  OXFORD_BLUE . ';'                          . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .mainmenu a:active, .mainmenu a:hover, .mainmenu a:visited  {'         . NEWLINE; 
      echo '   font-weight: bold;'                                                  . NEWLINE;
      echo '   font-size: 11pt;'                                                    . NEWLINE;
      echo '   text-decoration: none;'                                              . NEWLINE;
      echo '   color: ' .  html::get_contrast2_colour( $for_cms_deployment ) . ';'  . NEWLINE;
      echo ' }'                                                                     . NEWLINE;

      echo ' .bold {'                                                 . NEWLINE;
      echo '   font-weight: bold;'                                    . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      #-------------
      # Reports menu
      #-------------
      echo ' .reportsmenu {'                                                             . NEWLINE;
      echo '   background: ' .  html::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                          . NEWLINE;

      echo ' .reportsmenu table {'                                                             . NEWLINE;
      echo '   border-left-style: solid;'                                                      . NEWLINE;
      echo '   border-left-color: ' .  html::get_contrast1_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo '   border-left-width: 20px;'                                                       . NEWLINE;
      echo '   border-collapse: collapse;'                                                     . NEWLINE;
      echo ' }'                                                                                . NEWLINE;

      echo ' .reportsmenu td, reportsmenu th {'                       . NEWLINE;
      echo '    border-style: solid;'                                 . NEWLINE;
      echo '    border-width: 1px;'                                   . NEWLINE;
      echo '    border-color: white;'                                 . NEWLINE;
      echo '    border-collapse: collapse;'                           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      #----------------
      # Tables: non-CMS
      #----------------
      echo ' td.rightaligned, p.rightaligned {'                       . NEWLINE;
      echo '   text-align:right;'                                     . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' th.rightaligned {'                                       . NEWLINE;
      echo '   text-align:right;'                                     . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' th.topaligned {'                                         . NEWLINE;
      echo '   vertical-align:top;'                                   . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' td.contrast1, th.contrast1 {'                                               . NEWLINE;
      echo '   background: ' .  html::get_contrast1_colour( $for_cms_deployment ) . ';'  . NEWLINE;
      echo ' }'                                                                          . NEWLINE;

      echo ' td.contrast2, th.contrast2 {'                                               . NEWLINE;
      echo '   background: ' .  html::get_contrast2_colour( $for_cms_deployment ) . ';'  . NEWLINE;
      echo ' }'                                                                          . NEWLINE;

      echo ' .highlight1 td, .highlight1 th {'                                           . NEWLINE;
      echo '   background: ' .  html::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                          . NEWLINE;

      echo ' .highlight2 td, .highlight2 th {'                                           . NEWLINE;
      echo '   background: ' .  html::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                          . NEWLINE;

      echo ' table.datatab {'                                         . NEWLINE;
      echo '    margin-top: 5px;'                                     . NEWLINE;
      echo '    border-style: solid;'                                 . NEWLINE;
      echo '    border-width: 1px;'                                   . NEWLINE;
      echo '    border-color: black;'                                 . NEWLINE;
      echo '    border-collapse: collapse;'                           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .datatab td {'                                           . NEWLINE;
      echo '    border-style: solid;'                                 . NEWLINE;
      echo '    border-width: 1px;'                                   . NEWLINE;
      echo '    border-color: black;'                                 . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .datatab th {'                                           . NEWLINE;
      echo '    border-style: solid;'                                 . NEWLINE;
      echo '    border-width: 1px;'                                   . NEWLINE;
      echo '    border-color: black;'                                 . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .spacepadded td {'                                       . NEWLINE;
      echo '    padding: 5px;'                                        . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .spacepadded th {'                                       . NEWLINE;
      echo '    padding: 5px;'                                        . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .widelyspacepadded td {'                                 . NEWLINE;
      echo '    padding: 10px;'                                       . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .widelyspacepadded th {'                                 . NEWLINE;
      echo '    padding: 10px;'                                       . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' table.nointernalborders {'                               . NEWLINE;
      echo '    border-collapse: collapse;'                           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' table.boxed {'                                           . NEWLINE;
      echo '    border-style: solid;'                                 . NEWLINE;
      echo '    border-width: 1px;'                                   . NEWLINE;
      echo '    border-color: black;'                                 . NEWLINE;
      echo '    border-collapse: collapse;'                           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' td.boxed {'                                              . NEWLINE;
      echo '    border-style: solid;'                                 . NEWLINE;
      echo '    border-width: 1px;'                                   . NEWLINE;
      echo '    border-color: black;'                                 . NEWLINE;
      echo '    border-collapse: collapse;'                           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' table.contrast1_boxed {'                                                     . NEWLINE;
      echo '   border-style: solid;'                                                      . NEWLINE;
      echo '   border-color: ' .  html::get_contrast1_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo '   border-width: 1px;'                                                        . NEWLINE;
      echo '   border-collapse: collapse;'                                                . NEWLINE;
      echo ' }'                                                                           . NEWLINE;

      echo ' table.contrast2_boxed {'                                                     . NEWLINE;
      echo '   border-style: solid;'                                                      . NEWLINE;
      echo '   border-color: ' .  html::get_contrast2_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo '   border-width: 1px;'                                                        . NEWLINE;
      echo '   border-collapse: collapse;'                                                . NEWLINE;
      echo ' }'                                                                           . NEWLINE;

      echo ' table.bottomaligned {'                                   . NEWLINE;
      echo '   vertical-align:bottom;'                                . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .bottomaligned td {'                                     . NEWLINE;
      echo '   vertical-align:bottom;'                                . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' table.centeraligned {'                                   . NEWLINE;
      echo '   vertical-align:middle;'                                . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .centeraligned td {'                                     . NEWLINE;
      echo '   vertical-align: middle;'                                . NEWLINE;
      echo ' }'                                                       . NEWLINE;
    }

    #-------------------------------
    # Tables: CMS as well as non-CMS
    #-------------------------------

    echo ' .searchablefields table {'                                                   . NEWLINE;
    echo '   border-style: solid;'                                                      . NEWLINE;
    echo '   border-color: ' .  html::get_contrast1_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo '   border-width: 1px;'                                                        . NEWLINE;
    echo '   border-collapse: collapse;'                                                . NEWLINE;
    echo ' }'                                                                           . NEWLINE;

    echo ' .searchablefields td {'                                  . NEWLINE;
    echo '   padding: 5px;'                                         . NEWLINE;
    echo '   vertical-align:middle;'                                . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .searchablefields th {'                                  . NEWLINE;
    echo '   padding: 5px;'                                         . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .searchablefieldname {'                                                          . NEWLINE;
    echo '   text-align:right;'                                                              . NEWLINE;
    echo ' }'                                                                                . NEWLINE;
    echo NEWLINE;

    echo ' .searchablefieldvalue {'                                                          . NEWLINE;
    echo '   text-align:right;'                                                              . NEWLINE;
    echo ' }'                                                                                . NEWLINE;
    echo NEWLINE;

    echo ' td.sectionhead {'                                                             . NEWLINE;
    echo '   font-weight: bold;'                                                         . NEWLINE;
    if( ! $for_cms_deployment ) echo '   font-size: 11pt;'                               . NEWLINE;
    if( ! $for_cms_deployment ) echo '   font-style: italic;'                            . NEWLINE;
    echo ' }'                                                                            . NEWLINE;
    echo NEWLINE;


    echo ' .queryresults table {'                                   . NEWLINE;
    echo '    margin-top: 5px;'                                     . NEWLINE;
    echo '    border-style: solid;'                                 . NEWLINE;
    echo '    border-width: 1px;'                                   . NEWLINE;
    echo '    border-color: black;'                                 . NEWLINE;
    echo '    border-collapse: collapse;'                           . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .queryresults td {'                                      . NEWLINE;
    echo '    border-style: solid;'                                 . NEWLINE;
    echo '    border-width: 1px;'                                   . NEWLINE;
    echo '    border-color: black;'                                 . NEWLINE;
    echo '    padding: 5px;'                                        . NEWLINE;
    echo '    vertical-align:top;'                                  . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .queryresults th {'                                      . NEWLINE;
    echo '    border-style: solid;'                                 . NEWLINE;
    echo '    border-width: 1px;'                                   . NEWLINE;
    echo '    border-color: black;'                                 . NEWLINE;
    echo '    padding: 5px;'                                        . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    if( ! $for_cms_deployment ) { # CMS apparently already does space-padding of listitems

      echo ' .queryresults ul, .printableacrosspage ul {'           . NEWLINE;
      echo '    margin-top: 0px;'                                   . NEWLINE;
      echo '    margin-bottom: 0px;'                                . NEWLINE;
      echo '    margin-left: 2px;'                                  . NEWLINE;
      echo '    padding-left: 10px;'                                . NEWLINE;
      echo '    padding-top: 0px;'                                  . NEWLINE;
      echo ' }'                                                     . NEWLINE;
      echo ' .queryresults li, .printableacrosspage li  {'          . NEWLINE;
      echo '    margin-bottom: 8px;'                                . NEWLINE;
      echo '    margin-left: 0px;'                                  . NEWLINE;
      echo ' }'                                                     . NEWLINE;

      echo ' .printabledownpage ul {'                               . NEWLINE;
      echo '    margin-top: 0px;'                                   . NEWLINE;
      echo '    margin-bottom: 0px;'                                . NEWLINE;
      echo ' }'                                                     . NEWLINE;
      echo ' .printabledownpage li {'                               . NEWLINE;
      echo '    margin-bottom: 8px;'                                . NEWLINE;
      echo ' }'                                                     . NEWLINE;
    };

    echo NEWLINE;

    echo ' .printableacrosspage td {'                               . NEWLINE;
    echo '    vertical-align:top;'                                  . NEWLINE;
    echo '    padding: 10px;'                                       . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .printableacrosspage th {'                               . NEWLINE;
    echo '    padding: 10px;'                                       . NEWLINE;
    echo ' }'                                                       . NEWLINE;
    echo NEWLINE;

    echo ' .printabledownpage td {'                                 . NEWLINE;
    echo '    vertical-align:top;'                                  . NEWLINE;
    echo '    padding: 5px;'                                        . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .printabledownpage th {'                                 . NEWLINE;
    echo '    padding: 5px;'                                        . NEWLINE;
    echo ' }'                                                       . NEWLINE;
    echo NEWLINE;


    #-----------------------------------
    # Error messages, warnings 
    # and highlighting of important info
    #-----------------------------------

    echo ' div.highlight1, p.highlight1 {'                                             . NEWLINE;
    echo '   background: ' .  html::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo ' }'                                                                          . NEWLINE;

    echo ' div.highlight2, p.highlight2 {'                                             . NEWLINE;
    echo '   background: ' .  html::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo ' }'                                                                          . NEWLINE;

    echo ' p.contrast1 {'                                                              . NEWLINE;
    echo '   background: ' .  html::get_contrast1_colour( $for_cms_deployment ) . ';'  . NEWLINE;
    echo ' }'                                                                          . NEWLINE;

    echo ' p.contrast2 {'                                                              . NEWLINE;
    echo '   background: ' .  html::get_contrast2_colour( $for_cms_deployment ) . ';'  . NEWLINE;
    echo ' }'                                                                          . NEWLINE;

    echo ' td.highlight1, th.highlight1  {'                                            . NEWLINE;
    echo '   background: ' .  html::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo ' }'                                                                          . NEWLINE;

    echo ' td.highlight2, th.highlight2 {'                                             . NEWLINE;
    echo '   background: ' .  html::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo ' }'                                                                          . NEWLINE;

    echo NEWLINE;

    echo '.errmsg {'                                                                         . NEWLINE;
    echo '   background-color: ' .  html::get_contrast2_colour( $for_cms_deployment ) . ';'  . NEWLINE;
    echo '   font-size: 11pt;'                                                               . NEWLINE;
    echo '   font-weight: bold;'                                                             . NEWLINE;
    echo '   color: white;'                                                                  . NEWLINE;
    echo ' }'                                                                                . NEWLINE;

    echo '.warning {'                                                                        . NEWLINE;
    echo '   background-color: ' .  html::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo '   color: ' .  html::get_contrast2_colour( $for_cms_deployment ) . ';'             . NEWLINE;
    echo '   font-size: 11pt;'                                                               . NEWLINE;
    echo '   font-weight: bold;'                                                             . NEWLINE;
    echo ' }'                                                                                . NEWLINE;
    echo NEWLINE;

    #-------------
    # Entry fields
    #-------------
    #if( $for_cms_deployment ) {  # View menu, Text Size, Larger in IE6 doesn't increase size of dropdown list font
                                  # so will have to leave our dropdowns and buttons big to keep things accessible.
      #echo ' input, select, option { font-size: ' . CMS_FONT_SIZE . ' ; }'                   . NEWLINE;
    #}

    #if( $for_cms_deployment ) {  # no need for it to be bold if the font is large
      #echo ' .searchablefields input { font-weight: bold ; }'                               . NEWLINE;
    #}

    echo ' input.highlight1, select.highlight1, option.highlight1 {'                         . NEWLINE;
    echo '   background-color: ' .  html::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo ' }'                                                                                . NEWLINE;

    echo ' input.highlight2, select.highlight2, option.highlight2 {'                         . NEWLINE;
    echo '   background-color: ' .  html::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo ' }'                                                                                . NEWLINE;

    echo ' input.bold, option.bold {'                               . NEWLINE;
    echo '   font-weight: bold;'                                    . NEWLINE;
    echo ' }'                                                       . NEWLINE;
    echo NEWLINE;

    #------------------
    # Forms and buttons 
    #------------------
    echo '.dataentrytextfields label  { position: absolute; text-align:right; width:130px; }' . NEWLINE;
    echo '.dataentrytextfields input, .dataentrytextfields textarea, .dataentrytextfields select '
         . ' { margin-left: 140px; }'                                                         . NEWLINE;

    echo ' .buttonrow form {'                                       . NEWLINE; 
    echo '   display: inline; '                                     . NEWLINE;
    echo '   float:left; '                                          . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .buttonrow br { '                                        . NEWLINE;
    echo '   clear: left; '                                         . NEWLINE;
    echo '   line-height: 35px; '                                   . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    #if( $for_cms_deployment ) {  # "View, Text Size, Larger" in IE6 doesn't increase size of dropdown list font
                                  # so will have to leave our dropdowns and buttons big to keep things accessible.
      #echo ' .buttonrow input {font-size: ' . CMS_FONT_SIZE . '; }' . NEWLINE;

    if( ! $for_cms_deployment ) {
      echo ' .buttonrow a:link, .buttonrow a:visited  {'              . NEWLINE; 
      echo '   font-weight: bold;'                                    . NEWLINE;
      echo '   text-decoration: none;'                                . NEWLINE;
      echo '   color: ' .  OXFORD_BLUE . ';'                          . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .buttonrow a:active, .buttonrow a:hover  {'                           . NEWLINE; 
      echo '   font-weight: bold;'                                                 . NEWLINE;
      echo '   text-decoration: none;'                                             . NEWLINE;
      echo '   color: ' .  html::get_contrast2_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                    . NEWLINE;
    }
    echo NEWLINE;


    echo ' .pagination form {'                                      . NEWLINE; 
    echo '   display: inline; '                                     . NEWLINE;
    echo '   float:left; '                                          . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' input.pagelist  {'                                                                . NEWLINE;
    if( $for_cms_deployment ) echo '   font-size: ' . CMS_FONT_SIZE . ' ;'                   . NEWLINE;
    echo '   color: ' .  OXFORD_BLUE . ';'                                                   . NEWLINE;
    echo '   background-color: ' .  html::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo '   border-style: solid;'                                                           . NEWLINE;
    echo '   border-width: 2px;'                                                             . NEWLINE;
    echo '   border-color: white ;'                                                          . NEWLINE; 
    echo '   padding: 2px; '                                                                 . NEWLINE;
    echo ' }'                                                                                . NEWLINE;

    echo ' input.currpage  {'                                                                . NEWLINE;
    echo '   font-weight: bold;'                                                             . NEWLINE;
    if( $for_cms_deployment ) echo '   font-size: ' . CMS_FONT_SIZE . ' ;'                   . NEWLINE;
    echo '   color: ' .  html::get_contrast2_colour() . ';'                                  . NEWLINE;
    echo '   background-color: ' .  html::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo '   border-style: solid;'                                                           . NEWLINE;
    echo '   border-width: 1px;'                                                             . NEWLINE;
    echo '   border-color: ' . html::get_contrast2_colour() . ' ;'                           . NEWLINE;
    echo '   padding: 2px; '                                                                 . NEWLINE;
    echo ' }'                                                                                . NEWLINE;


    echo ' input.dummypage  {'                                                               . NEWLINE;
    echo '   color: ' .  OXFORD_BLUE . ';'                                                   . NEWLINE;
    if( $for_cms_deployment ) echo '   font-size: ' . CMS_FONT_SIZE . ' ;'                   . NEWLINE;
    echo '   background-color: white ;'                                                      . NEWLINE;
    echo '   border-style: solid;'                                                           . NEWLINE;
    echo '   border-left-width: 2px;'                                                        . NEWLINE;
    echo '   border-right-width: 2px;'                                                       . NEWLINE;
    echo '   border-top-width: 4px;'                                                         . NEWLINE;
    echo '   border-bottom-width: 4px;'                                                      . NEWLINE;
    echo '   border-color: white;'                                                           . NEWLINE;
    echo '   float:left; '                                                                   . NEWLINE;
    echo ' }'                                                                                . NEWLINE;

    echo ' .pagination br { '                                                                . NEWLINE;
    echo '   clear: left; '                                                                  . NEWLINE;
    echo '   line-height: 35px; '                                                            . NEWLINE;
    echo ' }'                                                                                . NEWLINE;
    echo NEWLINE;

    echo ' span.narrowspaceonleft {'                                                         . NEWLINE;
    echo '   margin-left: 5px;'                                                              . NEWLINE;
    echo ' }'                                                                                . NEWLINE;
    echo NEWLINE;

    echo ' .choosepresentation {'                                                              . NEWLINE;
    echo '   background-color: ' .  html::get_highlight1_colour( $for_cms_deployment ) . ';'   . NEWLINE;
    echo '   border-style: solid;'                                                             . NEWLINE;
    echo '   border-color: ' .  html::get_contrast1_colour( $for_cms_deployment ) . ';'        . NEWLINE;
    echo '   border-width: 1px;'                                                               . NEWLINE;
    echo '   padding-top: 10px;'                                                               . NEWLINE;
    echo '   padding-bottom: 10px;'                                                            . NEWLINE;
    echo '   padding-left: 10px;'                                                              . NEWLINE;
    if( $for_cms_deployment ) echo '   padding-right: 10px;'                                   . NEWLINE;
    echo ' }'                                                                                  . NEWLINE;

    echo ' .choosepresentation input {'                                                        . NEWLINE;
    echo '   margin-left: 2px;'                                                                . NEWLINE;
    echo '   margin-right: 2px;'                                                               . NEWLINE;
    echo ' }'                                                                                  . NEWLINE;
    echo NEWLINE;

    echo ' .choosepresentation select {'                                                       . NEWLINE;
    echo '   margin-right: 20px;'                                                              . NEWLINE;
    echo ' }'                                                                                  . NEWLINE;
    echo NEWLINE;

    echo '</style>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function get_highlight1_colour( $for_cms_deployment = FALSE ) {

    if( $for_cms_deployment )
      return CMS_WEDGWOOD_BLUE;

    else {  # different colour scheme for training and live DBs
      if( CONSTANT_DATABASE_TYPE == 'test' )
        return PASTEL_BROWN;
      else
        return PASTEL_BLUE;
    }
  }
  #-----------------------------------------------------------------

  function get_highlight2_colour( $for_cms_deployment = FALSE ) {

    if( $for_cms_deployment )
      return CMS_LILAC;

    else {  # different colour scheme for training and live DBs
      if( CONSTANT_DATABASE_TYPE == 'test' )
        return PASTEL_BLUE;
      else
        return PASTEL_OLIVE;
    }
  }
  #-----------------------------------------------------------------

  function get_contrast1_colour( $for_cms_deployment = FALSE ) {

    if( CONSTANT_DATABASE_TYPE == 'test' )  # different colour schemes for training and live DBs
      return PASTEL_GREEN;
    else
      return MID_GREEN;
  }
  #-----------------------------------------------------------------

  function get_contrast2_colour( $for_cms_deployment = FALSE ) {
    return DARK_RED;
  }
  #-----------------------------------------------------------------

  function get_contrast3_colour( $for_cms_deployment = FALSE ) {

    if( $for_cms_deployment )
      return CMS_INDIGO;
    else
      return DARK_GREEN;
  }
  #-----------------------------------------------------------------

  function get_oxford_blue( $for_cms_deployment = FALSE ) {
    return OXFORD_BLUE;
  }
  #-----------------------------------------------------------------

  function html_head_end() {
    echo NEWLINE;
    echo '</head>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function body_start( $focus_form = NULL, $focus_field = NULL, $required_anchor = NULL ) {

    echo NEWLINE;
    echo '<body ';

    if(( $focus_form && $focus_field ) || $required_anchor ) {
      echo ' onLoad="';

      if( $required_anchor ) {
        echo "location.href='";
        if( substr( $required_anchor, 0, 1 ) != '#' ) echo '#';
        echo "$required_anchor'; ";
      }

      if( $focus_form && $focus_field ) {  # Set focus on a particular field
        echo 'document.';
        echo $focus_form;
        echo '.';
        echo $focus_field;
        echo '.focus() ';
      }
      echo '"';
    }

    echo ' >';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------
  function body_end() {

    echo NEWLINE;
    echo '</body>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------
  function h1_start() {
    echo '<h1>';
  }
  #-----------------------------------------------------------------
  function h1_end() {
    echo '</h1>';
  }
  #-----------------------------------------------------------------
  function h2_start() {
    echo '<h2>';
  }
  #-----------------------------------------------------------------
  function h2_end() {
    echo '</h2>';
  }
  #-----------------------------------------------------------------
  function h3_start() {
    echo '<h3>';
  }
  #-----------------------------------------------------------------
  function h3_end() {
    echo '</h3>';
  }
  #-----------------------------------------------------------------
  function h4_start() {
    echo '<h4>';
  }
  #-----------------------------------------------------------------
  function h4_end() {
    echo '</h4>';
  }
  #-----------------------------------------------------------------
  function h5_start() {
    echo '<h5>';
  }
  #-----------------------------------------------------------------
  function h5_end() {
    echo '</h5>';
  }
  #-----------------------------------------------------------------
  function bold_start() {
    echo '<strong>';
  }
  #-----------------------------------------------------------------
  function bold_end() {
    echo '</strong>';
  }
  #-----------------------------------------------------------------
  function italic_start() {
    echo '<em>';
  }
  #-----------------------------------------------------------------
  function italic_end() {
    echo '</em>';
  }
  #-----------------------------------------------------------------

  function linebreak( $parms = NULL ) {

    echo '<br ';
    echo $parms;
    echo '/>';
    echo NEWLINE;
  }

  #-----------------------------------------------------------------

  function space() {

    echo SPACE;
  }

  #-----------------------------------------------------------------

  function comment( $parms = NULL ) {

    echo '<!-- ';
    if( $parms ) echo $parms;
    echo '-->' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function new_paragraph( $parms = NULL ) {

    echo NEWLINE . '<p ';
    if( $parms ) echo $parms;
    echo '/>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function div_start( $parms = NULL ) {

    echo NEWLINE . '<div ';
    if( $parms ) echo $parms;
    echo '>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function div_end( $div_class = NULL, $div_id = NULL ) {

    echo NEWLINE . '</div>';
    echo NEWLINE;
    if( $div_class ) html::comment("End class $div_class");
    if( $div_id )    html::comment("End ID $div_id");
  }
  #-----------------------------------------------------------------

  function span_start( $parms = NULL ) {

    echo NEWLINE . '<span ';
    if( $parms ) echo $parms;
    echo '>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function span_end( $parms = NULL ) {

    echo NEWLINE . '</span>';
    echo NEWLINE;
    if( $parms ) html::comment("End $parms");
  }
  #-----------------------------------------------------------------

  function form_start( $class_name, $method_name, $form_name = '', $form_target = '',
                       $onsubmit_validation = FALSE, $form_destination='', $form_method='POST',
                       $parms = NULL ) {


    echo NEWLINE . '<form action="';

    if( $form_destination != '' ) 
      echo $form_destination;
    else
      echo $_SERVER['PHP_SELF'];
    echo '" ';

    if( "$form_name" != "" ) {
      echo ' name="' . $form_name . '" ';
      echo ' id="' . $form_name . '" ';
    }
    elseif( "$method_name" != "" ) {
      $form_name = $class_name . '_' . $method_name;
      echo ' name="' . $form_name . '" ';
      echo ' id="' . $form_name . '" ';
    }

    switch( strtolower( $form_target )) {
     case '_self':
     case '_blank':
     case '_top':
     case '_parent':
       echo ' target="' . strtolower( $form_target ) . '" ';
       break;
     default:
       break;
    }

    if( $onsubmit_validation ) echo ' onsubmit="return js_check_form_valid( this )" ' ;
    
    if( $parms ) echo ' ' . $parms . ' ';
    
    echo '  method="' . $form_method . '">';
    echo NEWLINE;

    echo '<input type="hidden" name="class_name"  value="';
    echo html::call_htmlentities( $class_name );
    echo '" />' . NEWLINE;

    echo '<input type="hidden" name="method_name"  value="';
    echo html::call_htmlentities( $method_name );
    echo '" />' . NEWLINE;

    if( $_SESSION['session_token'] ) {
      echo '<input type="hidden" name="' . SESSION_TOKEN_FIELD . '"  value="';
      echo html::call_htmlentities( $_SESSION['session_token'] );
      echo '" />' . NEWLINE;
    }

    echo '<input type="hidden" name="requires_validation" id="requires_validation" value="0" >' . NEWLINE;
    echo '<input type="hidden" name="validation_err" id="validation_err" value="0" >' . NEWLINE;
    echo '<input type="hidden" name="cancel_submission" id="cancel_submission" value="0" >' . NEWLINE;

    return $form_name;
  }
  #-----------------------------------------------------------------

  function form_end() {
    echo NEWLINE . '</form>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function link( $href, $displayed_text, $title = '', $target = '_self', $accesskey = '', $tabindex = 1,
                 $extra_parms = NULL ) {

    echo NEWLINE;

    echo '<a href="' . $href . '" ';

    if( $title != '' )     echo ' title="' . $title . '" ';
    if( $target != '' )    echo ' target="' . $target . '" ';
    if( $accesskey != '' ) echo ' accesskey="' . $accesskey . '" ';
    if( $tabindex > 0 )    echo ' tabindex="' . $tabindex . '" ';

    if( $extra_parms ) echo ' ' . $extra_parms . ' ';

    echo '>' . NEWLINE;

    echo $displayed_text;

    echo NEWLINE . '</a>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function return_link( $href, $displayed_text, $title = '', $target = '_self', $accesskey = '', $tabindex = 1,
                        $extra_parms = NULL ) {

    $linkval = NEWLINE;

    $linkval = $linkval . '<a href="' . $href . '" ';

    if( $title != '' )     $linkval = $linkval . ' title="' . $title . '" ';
    if( $target != '' )    $linkval = $linkval . ' target="' . $target . '" ';
    if( $accesskey != '' ) $linkval = $linkval . ' accesskey="' . $accesskey . '" ';
    if( $tabindex > 0 )    $linkval = $linkval . ' tabindex="' . $tabindex . '" ';

    if( $extra_parms ) echo ' ' . $extra_parms . ' ';

    $linkval = $linkval . '>' . NEWLINE;

    $linkval = $linkval . $displayed_text;

    $linkval = $linkval . NEWLINE . '</a>' . NEWLINE;
    return $linkval;
  }
  #-----------------------------------------------------------------

  function link_start( $href, $title = '', $target = '_self', $accesskey = '', $tabindex = 1,
                       $extra_parms = NULL ) {

    echo NEWLINE;

    echo '<a href="' . $href;

    # Add session token to all links which take you to another page.
    if( ! strstr( $href, '#' )) { # not a local link within the same page
      if( strstr( $href, '?' ))   # there is already one parameter in the href
        echo '&' . SESSION_TOKEN_FIELD . '=' . rawurlencode( $_SESSION['session_token'] );
      else
        echo '?' . SESSION_TOKEN_FIELD . '=' . rawurlencode( $_SESSION['session_token'] );
    }
    echo '" ';

    if( $title != '' )     echo ' title="' . $title . '" ';
    if( $target != '' )    echo ' target="' . $target . '" ';
    if( $accesskey != '' ) echo ' accesskey="' . $accesskey . '" ';
    if( $tabindex > 0 )    echo ' tabindex="' . $tabindex . '" ';

    if( $extra_parms ) echo ' ' . $extra_parms . ' ';

    echo '>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function link_end() {
    echo NEWLINE . '</a>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function anchor( $anchor_name = NULL ) {

    if( $anchor_name != NULL ) {
      echo NEWLINE . '<a name="' . $anchor_name . '" id="' . $anchor_name . '" >';
      echo '</a>' . NEWLINE;
    }
  }
  #-----------------------------------------------------------------

  function field_id_from_fieldname( $fieldname, $input_instance = 0 ) {

    $field_id = $fieldname;
    if( $input_instance ) {
      if( substr( $field_id, -2 ) == '[]' ) # it's a PHP array, but not a valid ID for CSS
        $field_id = substr( $field_id, 0, -2 );
      $field_id = $field_id . $input_instance;
    }
    return $field_id;
  }
  #-----------------------------------------------------------------

  function hidden_field( $fieldname, $value = NULL, $input_instance = 0 ) {

    echo NEWLINE;

    $field_id = html::field_id_from_fieldname( $fieldname, $input_instance );

    echo '<input type="hidden" name="' . $fieldname . '" id="' . $field_id . '"  value="';
    echo html::call_htmlentities( $value );
    echo '" />' . NEWLINE;

  }
  #-----------------------------------------------------------------

  function input_field( $fieldname, $label = '', $value = NULL, $in_table = FALSE, $size = NULL, $tabindex=1,
                        $label_parms = NULL, $data_parms = NULL, $input_parms = NULL, $input_instance = 0,
                        $trailing_text = NULL ) {

    $field_id = html::field_id_from_fieldname( $fieldname, $input_instance );

    echo NEWLINE;
    if( $in_table ) { 
      echo '<td';
      if( $label_parms ) echo ' ' . $label_parms . ' ';
      echo '>';
    }

    echo '<label for="' . $field_id . '"';
    if( ! $in_table ) { 
      if( $label_parms ) echo ' ' . $label_parms . ' ';
    }
    echo '>';
    $label = trim( $label );
    echo $label;
    $last_char = substr( $label, -1 );
    if( $label != '' && $last_char != ':' && $last_char != '.' && $last_char != '*' && $last_char != '£' ) echo ':';
    echo ' </label>';

    if( $in_table ) {
      echo '</td>';
      echo '<td';
      if( $data_parms ) echo ' ' . $data_parms . ' ';
      echo '>';
    }

    echo '<input type="input" name="' . $fieldname . '" id="' . $field_id . '" value="';
    echo html::call_htmlentities( $value );
    echo '"';
    if( $size != NULL ) echo ' size="' . $size . '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    if( $input_parms != NULL ) echo " $input_parms ";
    echo ' />';

    if( $trailing_text ) echo ' ' . $trailing_text;

    if( $in_table ) echo '</td>';
    echo NEWLINE;

  }
  #-----------------------------------------------------------------

  function password_field( $fieldname, $label = '', $in_table = FALSE, $size = NULL, $tabindex=1 ) {

    echo NEWLINE;
    if( $in_table ) echo '<td>';

    echo '<label for="' . $fieldname . '">';
    $label = trim( $label );
    echo $label;
    $last_char = substr( $label, -1 );
    if( $label != '' && $last_char != ':' && $last_char != '.' && $last_char != '*' ) echo ':';
    echo ' </label>';

    if( $in_table ) echo '</td><td>';

    echo '<input type="password" name="' . $fieldname . '" id="' . $fieldname . '" value=""';

    if( $size != NULL ) echo ' size="' . $size . '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    echo ' />';

    if( $in_table ) echo '</td>';
    echo NEWLINE;

  }
  #-----------------------------------------------------------------

  function checkbox( $fieldname, $label, $is_checked = NULL, $value_when_checked = 1, $in_table = FALSE,
                     $tabindex=1, $input_instance = NULL, $parms = NULL, $label_on_left = FALSE ) {

    $field_id = html::field_id_from_fieldname( $fieldname, $input_instance );

    echo NEWLINE;
    if( $in_table ) echo '<td>';

    if( $label_on_left ) {
      echo '<label for="' . $field_id . '">';
      $label = trim( $label );
      echo $label;
      echo '</label>';
    }

    echo '<input type="checkbox" name="' . $fieldname . '" id="' . $field_id . '" value="';
    echo html::call_htmlentities( $value_when_checked );
    echo '"';
    if( $is_checked ) echo ' CHECKED ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    if( $parms ) echo ' ' . $parms . ' ';
    echo ' />';

    if( $in_table ) echo '</td><td>';

    if( ! $label_on_left ) {
      echo '<label for="' . $field_id . '">';
      $label = trim( $label );
      echo $label;
      echo '</label>';
    }

    if( $in_table ) echo '</td>';
    echo NEWLINE;

  }
  #-----------------------------------------------------------------

  function checkbox_with_label_on_left( $fieldname, $label, $is_checked = NULL, $value_when_checked = 1, 
                                        $in_table = FALSE, $tabindex=1, $input_instance = NULL, $parms = NULL ) {

    html::checkbox( $fieldname, $label, $is_checked, $value_when_checked, 
                    $in_table, $tabindex, $input_instance, $parms, $label_on_left = TRUE );
  }
  #-----------------------------------------------------------------

  function radio_button( $fieldname, $label, $value_when_checked, $current_value = 0, $tabindex=1, 
                         $button_instance=0, $script=NULL ) {

    echo NEWLINE;

    $id = $fieldname;
    if( $button_instance ) $id = $id . $button_instance;
 
    echo '<label for="' . $id . '">';

    echo '<input type="radio" name="' . $fieldname . '" id="' . $id . '" value="';
    echo html::call_htmlentities( $value_when_checked );
    echo '"';
    if( $current_value == $value_when_checked ) echo ' CHECKED ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';

    if( $script ) echo ' ' . $script . ' ';

    echo ' />';

    $label = trim( $label );
    echo $label;
    echo '</label>';

    echo NEWLINE;

  }
  #-----------------------------------------------------------------

  function printed_checkbox( $fieldname, $label, $is_checked = NULL, $value_when_checked = NULL, $in_table = FALSE,
                             $tabindex=1 ) {
    echo '[';
    if( $is_checked )
      echo ' X ';
    else
      echo SPACE . SPACE;
    echo ']=';
    echo $label;
  }
  #-----------------------------------------------------------------

  function dropdown_start( $fieldname, $label, $in_table = FALSE, $script = NULL, $tabindex=1, 
                           $label_parms = NULL, $input_instance = 0 ) {

    echo NEWLINE;

    $field_id = html::field_id_from_fieldname( $fieldname, $input_instance );

    if( $in_table ) echo '<td ' . $label_parms . ' >';
    if( strlen( $label ) > 0 ) {
      echo '<label for="' . $fieldname . '" ';
      if( ! $in_table ) echo $label_parms;
      echo '>';
    }
    $label = trim( $label );
    echo $label;
    $last_char = substr( $label, -1 );
    if( strlen($label) > 0 && $last_char != ':' && $last_char != '.' && $last_char != '*' ) echo ':';
    if( strlen( $label ) > 0 ) echo ' </label>';

    if( $in_table ) echo '</td><td>';

    echo '<select name="' . $fieldname . '" id="' . $field_id . '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" '; 

    if( $script ) echo $script;  # N.B. You need the "on" event and the actual script in double quotes

    echo ' >';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function dropdown_option( $internal_value, $displayed_value, $selection = NULL, $parms = NULL ) {

    echo '<option value="' . $internal_value . '" ';
    if( $selection == $internal_value ) echo ' selected';
    if( $parms ) echo ' ' . $parms . ' ';
    echo '>';
    echo $displayed_value;
    echo '</option>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function dropdown_optgroup_start( $label = NULL ) {
    echo NEWLINE;
    echo '<optgroup label="';
    echo $label;
    echo '">';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function dropdown_optgroup_end() {

    echo NEWLINE;
    echo '</optgroup>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function dropdown_end( $in_table = FALSE ) {
    echo NEWLINE;
    echo '</select>';
    if( $in_table ) echo '</td>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function textarea_start( $fieldname, $rows = 3, $cols = 50, $label=NULL, $textarea_parms=NULL, $label_parms=NULL, 
                           $input_instance = NULL ) {

    echo NEWLINE;

    $field_id = html::field_id_from_fieldname( $fieldname, $input_instance );

    if( $label ) {
      echo '<label for="' . $field_id . '"';
      if( $label_parms ) echo ' ' . $label_parms . ' ';
      echo '>';
      $label = trim( $label );
      echo $label;
      $last_char = substr( $label, -1 );
      if( $label != '' && $last_char != ':' && $last_char != '.' && $last_char != '*' && $last_char != '£' ) echo ':';
      echo ' </label>';
    }

    echo '<textarea name="' . $fieldname . '" id="' . $field_id . '" rows="' . $rows . '" cols="' . $cols . '" ';
    if( $textarea_parms ) echo $textarea_parms;
    echo ' tabindex="1" >';
  }
  #-----------------------------------------------------------------

  function textarea_end() {

    echo '</textarea>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------


  function textarea( $fieldname, $rows=3, $cols=50, $value=NULL, $label=NULL, $textarea_parms=NULL, $label_parms=NULL,
                     $input_instance = NULL )
  {

    echo NEWLINE;

    $field_id = html::field_id_from_fieldname( $fieldname, $input_instance );

    if( $label ) {
      echo '<label for="' . $field_id . '"';
      if( $label_parms ) echo ' ' . $label_parms . ' ';
      echo '>';
      $label = trim( $label );
      echo $label;
      $last_char = substr( $label, -1 );
      if( $label != '' && $last_char != ':' && $last_char != '.' && $last_char != '*' && $last_char != '£' ) echo ':';
      echo ' </label>';
    }

    echo '<textarea name="' . $fieldname . '" id="' . $field_id . '" rows="' . $rows . '" cols="' . $cols . '" ';
    if( $textarea_parms ) echo $textarea_parms;
    echo ' tabindex="1" >';
    echo $value;
    echo '</textarea>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function submit_button( $button_name = 'ok_button', $value = 'OK', $tabindex = 1, $other_parms = NULL ) {

    echo NEWLINE;

    echo '<input type="submit" name="' . $button_name . '"  value="';
    echo html::call_htmlentities( $value );
    echo '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    if( $other_parms ) 
      echo ' ' . $other_parms . ' ';
    elseif( $button_name == 'save_button' )
      echo ' accesskey="S" ';
    echo ' />' . NEWLINE;

  }
  #-----------------------------------------------------------------

  function submit_and_cancel_button( $button_name = 'cancel_button', $value = 'Cancel', $tabindex = 1, 
                                     $other_parms = NULL ) {

    echo NEWLINE;

    echo '<input type="submit" name="' . $button_name . '"  value="';

    echo html::call_htmlentities( $value );
    echo '" ';

    #----------------------------------------
    # Cancel the form's "onsubmit" validation
    #----------------------------------------
    echo  ' onclick="js_drop_form_validation( this )" ';

    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';

    if( $other_parms ) 
      echo ' ' . $other_parms . ' ';

    echo ' />' . NEWLINE;

  }
  #-----------------------------------------------------------------

  function cancel_button( $button_name = 'cancel_button', $value = 'Cancel', $tabindex = 1 ) {

    echo NEWLINE;

    echo '<input type="reset" name="' . $button_name . '"  value="';
    echo html::call_htmlentities( $value );
    echo '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    echo ' />' . NEWLINE;

  }
  #-----------------------------------------------------------------

  function button( $button_name = 'button', $value = 'Button', $tabindex = 1, $other_parms = NULL ) {

    echo NEWLINE;

    echo '<input type="button" name="' . $button_name . '"  value="';
    echo html::call_htmlentities( $value );
    echo '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    if( $other_parms ) 
      echo ' ' . $other_parms . ' ';
    echo ' />' . NEWLINE;

  }
  #-----------------------------------------------------------------

  function file_upload_field( $fieldname, $label = '', $value = NULL, $size = NULL, $tabindex=1,
                              $label_parms = NULL, $input_parms = NULL, $input_instance = 0 ) {

    $field_id = html::field_id_from_fieldname( $fieldname, $input_instance );

    echo NEWLINE;

    echo '<label for="' . $field_id . '">';
    $label = trim( $label );
    echo $label;
    $last_char = substr( $label, -1 );
    if( $label != '' && $last_char != ':' && $last_char != '.' && $last_char != '*' && $last_char != '£' ) echo ':';
    echo ' </label>';

    echo '<input type="file" name="' . $fieldname . '" id="' . $field_id . '" value="';
    echo html::call_htmlentities( $value );
    echo '"';
    if( $size != NULL ) echo ' size="' . $size . '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    if( $input_parms != NULL ) echo " $input_parms ";
    echo ' />';

    echo NEWLINE;

  }
  #-----------------------------------------------------------------

  function multiple_file_upload_field( $fieldname, $label = '', $value = NULL, $size = NULL, 
                                       $tabindex=1, $label_parms = NULL, $input_parms = NULL, 
                                       $input_instance = 0 ) {

    if( $input_parms )
      $input_parms .= ' multiple="true"';
    else
      $input_parms = 'multiple="true"';

    html::file_upload_field( $fieldname, $label, $value, $size, $tabindex,
                              $label_parms, $input_parms, $input_instance );
  }
  #-----------------------------------------------------------------

  function label( $label_text, $label_id, $parms ) {  # free-standing label, not necessarily attached to field

    echo '<label ';
    if( $label_id ) echo ' id="' . $label_id . '" ';
    if( $parms ) echo ' ' . $parms . ' ';
    echo '>';

    echo $label_text;

    echo '</label>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function ulist_start( $parms = NULL ) {
    echo NEWLINE . '<ul';
    if( $parms ) echo ' ' . $parms . ' ';
    echo '>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function ulist_end() {
    echo NEWLINE . '</ul>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function listitem_start( $parms = NULL ) {
    echo NEWLINE . '<li';
    if( $parms ) echo ' ' . $parms . ' ';
    echo '>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function listitem_end() {
    echo NEWLINE . '</li>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function listitem( $the_value = NULL, $parms = NULL ) {
    echo NEWLINE . '<li';
    if( $parms ) echo ' ' . $parms . ' ';
    echo '>' . NEWLINE;
    echo $the_value;
    echo NEWLINE . '</li>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function horizontal_rule( $parms = NULL ) {

    echo NEWLINE . '<hr ';
    if( $parms ) echo $parms;
    echo '/>' . NEWLINE;
    echo LINEBREAK;
  }
  #-----------------------------------------------------------------

  function bullet_point() {

    echo ' &bull; ';
  }
  #-----------------------------------------------------------------

  function return_bullet_point() {

    return ' &bull; ';
  }
  #-----------------------------------------------------------------

  function table_start( $parms = NULL ) {
    echo NEWLINE;
    echo '<table ';
    echo $parms;
    echo '>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function table_end() {
    echo NEWLINE;
    echo '</table>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function table_caption( $caption = NULL, $attribs = NULL, $style = 'italic' ) {
    echo NEWLINE;
    echo '<caption';
    if( $attribs ) echo ' ' . $attribs . ' ' ;
    echo '>';

    if( $style == 'italic' )
      echo '<i>';
    elseif( $style == 'bold' )
      echo '<b>';

    echo $caption;

    if( $style == 'italic' )
      echo '</i>';
    elseif( $style == 'bold' )
      echo '</b>';

    echo '</caption>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function tablerow_start( $parms = NULL ) {
    echo NEWLINE;
    echo '<tr ';
    echo $parms;
    echo '>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function tablerow_end() {
    echo NEWLINE;
    echo '</tr>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function new_tablerow( $parms = NULL ) {  # end current table row and start a new one

    echo NEWLINE;
    echo '</tr>';
    echo NEWLINE;
    echo '<tr ';
    echo $parms;
    echo '>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function tabledata_start( $parms = NULL ) {
    echo NEWLINE;
    echo '<td ';
    echo $parms;
    echo '>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function tabledata_end() {
    echo NEWLINE;
    echo '</td>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function tabledata( $the_data = NULL, $parms = NULL ) {
    echo NEWLINE;
    echo '<td';
    if( $parms ) echo ' ' . $parms . ' ';
    echo '>';
    echo NEWLINE;
    echo $the_data;
    if( "$the_data" == "" ) echo SPACE;
    echo NEWLINE;
    echo '</td>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function table_head_start( $parms = NULL ) {

    echo '<thead';
    if ( $parms ) echo ' ' . $parms . ' ';
    echo '>';
  }
  #-----------------------------------------------------------------

  function table_head_end() {

    echo '</thead>';
  }
  #-----------------------------------------------------------------

  function table_body_start( $parms = NULL ) {

    echo '<tbody';
    if ( $parms ) echo ' ' . $parms . ' ';
    echo '>';
  }
  #-----------------------------------------------------------------

  function table_body_end() {

    echo '</tbody>';
  }
  #-----------------------------------------------------------------

  function column_header( $label = NULL, $parms = NULL ) {
    echo NEWLINE;
    echo '<th';
    if( $parms ) echo ' ' . $parms . ' ';
    echo '>';
    echo NEWLINE;
    echo $label;
    if( "$label" == "" ) echo SPACE;
    echo NEWLINE;
    echo '</th>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function page_top_anchor() {

    echo NEWLINE . '<a name="' . PAGE_TOP . '" >';
    echo '</a>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function page_bottom_anchor() {

    echo NEWLINE . '<a name="' . PAGE_BOTTOM . '" >';
    echo '</a>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  function link_to_page_top( $tabindex = 1, $title = 'Top of Page', $accesskey = '' ) {

    echo NEWLINE;
    echo '<a href="#' . PAGE_TOP  . '" target="_self" ';
    if( $title != '' )     echo ' title="' . $title . '" ';
    if( $accesskey != '' ) echo ' accesskey="' . $accesskey . '" ';
    if( $tabindex > 0 )    echo ' tabindex="' . $tabindex . '" ';
    echo '>' . NEWLINE;
    echo $title;
    echo NEWLINE . '</a>' . NEWLINE;
    if( $accesskey != '' ) echo ' (shortcut key ' . $accesskey . ')';
  }
  #-----------------------------------------------------

  function link_to_page_bottom( $tabindex = 1, $title = 'Bottom of Page', $accesskey = '' ) {

    echo NEWLINE;
    echo '<a href="#' . PAGE_BOTTOM  . '" target="_self" ';
    if( $title != '' )     echo ' title="' . $title . '" ';
    if( $accesskey != '' ) echo ' accesskey="' . $accesskey . '" ';
    if( $tabindex > 0 )    echo ' tabindex="' . $tabindex . '" ';
    echo '>' . NEWLINE;
    echo $title;
    echo NEWLINE . '</a>' . NEWLINE;
    if( $accesskey != '' ) echo ' (shortcut key ' . $accesskey . ')';
  }
  #-----------------------------------------------------

  function small_start() {
    echo '<small>';
  }
  #-----------------------------------------------------

  function small_end() {
    echo '</small>';
  }
  #-----------------------------------------------------

  function small( $the_text ) {
    echo '<small>';
    echo $the_text;
    echo '</small>';
    echo NEWLINE;
  }
  #-----------------------------------------------------

  function write_javascript_function( $script_body ) {

    echo NEWLINE;
    echo '<script type="text/javascript">';
    echo NEWLINE;
    echo '<!--';
    echo NEWLINE;

    echo $script_body;
    echo NEWLINE;

    echo '// -->';
    echo NEWLINE;
    echo '</script>';
    echo NEWLINE;
  }
  #-----------------------------------------------------
  function fieldset_start( $legend, $fieldset_name = NULL, $parms = NULL, $legend_parms = NULL ) {

    echo NEWLINE;
    echo '<fieldset ';
    if( $fieldset_name ) echo ' id="' . $fieldset_name . '" ';
    if( $parms ) echo ' ' . $parms . ' ';
    echo ' >';

    # Legend
    echo NEWLINE;
    echo '<legend ';
    if( $fieldset_name ) echo ' id="' . $fieldset_name . '_legend" ';
    if( $legend_parms ) echo ' ' . $legend_parms . ' ';
    echo ' >';
    echo html::call_htmlentities( $legend );
    echo '</legend>';
    
    echo NEWLINE;
  }
  #-----------------------------------------------------

  function fieldset_end( $desc ) {
    echo NEWLINE;
    echo '</fieldset>';
    if( $desc ) html::comment( 'End ' . $desc );
    echo NEWLINE;
  }
  #-----------------------------------------------------

  function pound_sign( $as_html_entity = FALSE ) {

    $pound_sign = '&pound;';
    if( ! $as_html_entity ) {
      $pound_sign = html_entity_decode( $pound_sign, ENT_QUOTES, 
                                        strtoupper( Application_Entity::get_character_encoding() ));
    }
    return $pound_sign;
  }
  #-----------------------------------------------------
}
?>
