<?php
/*
 * PHP parent class for all other classes in the Aeolus system.
 * Author: Sushila Burgess
 *
 */

class Infrastructure {
  #-----------------------------------------------------

  function Infrastructure() { 
  }
  #-----------------------------------------------------

  function is_search_results_method( $method_name ) { #is this a 'safe' method that does not allow data-entry?

    $valid_suffix = '_search_results';
    $actual_suffix = '';
    $actual_suffix = substr( $method_name, 0 - strlen( $valid_suffix ));
    if( $actual_suffix == $valid_suffix ) return TRUE;

    return FALSE;
  }
  #-----------------------------------------------------

  # We will try to use htmlentities() on all output, to avoid running malicious scripts which may have been
  # entered in free-text fields such as notes. BUT, just in case we miss a place where free text is output,
  # let's try as best we can to avoid the entry of malicious scripts in the first place.

  function contains_scripting( $parm_value ) {

    $parm_value = strtolower( $parm_value );

    if( $this->string_contains_substring( $parm_value, 'onfocus' ))  return TRUE;
    if( $this->string_contains_substring( $parm_value, 'onblur' ))   return TRUE;
    if( $this->string_contains_substring( $parm_value, 'onselect' )) return TRUE;
    if( $this->string_contains_substring( $parm_value, 'onchange' )) return TRUE;
    if( $this->string_contains_substring( $parm_value, 'onclick' )) return TRUE;
    if( $this->string_contains_substring( $parm_value, 'ondblclick' )) return TRUE;
    if( $this->string_contains_substring( $parm_value, 'onmousedown' )) return TRUE;
    if( $this->string_contains_substring( $parm_value, 'onmouseup' )) return TRUE;
    if( $this->string_contains_substring( $parm_value, 'onmouseover' )) return TRUE;
    if( $this->string_contains_substring( $parm_value, 'onmousemove' )) return TRUE;
    if( $this->string_contains_substring( $parm_value, 'onmouseout' )) return TRUE;
    if( $this->string_contains_substring( $parm_value, 'onkeypress' )) return TRUE;
    if( $this->string_contains_substring( $parm_value, 'onkeydown' )) return TRUE;
    if( $this->string_contains_substring( $parm_value, 'onkeyup' )) return TRUE;

    $parm_value = str_replace( ' ', '', $parm_value );
    $parm_value = str_replace( "\n", '', $parm_value );
    $parm_value = str_replace( "\r", '', $parm_value );
    $parm_value = str_replace( "\t", '', $parm_value );

    if( $this->string_contains_substring( $parm_value, '<script' ))  return TRUE;
    if( $this->string_contains_substring( $parm_value, 'script>' ))  return TRUE;
    
    return FALSE;
  }
  #-----------------------------------------------------
}
?>
