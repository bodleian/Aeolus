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

    # You MUST add your own criteria for deciding whether a particular method is safe.
    return TRUE;
  }
  #-----------------------------------------------------

  # We will try to use htmlentities() on all output, to avoid running malicious scripts which may have been
  # entered in free-text fields such as notes. BUT, just in case we miss a place where free text is output,
  # let's try as best we can to avoid the entry of malicious scripts in the first place.

  function contains_scripting( $parm_value ) {

    # You MUST add your own criteria for deciding whether a string contains scripting.
    return FALSE;
  }
  #-----------------------------------------------------
}
?>
