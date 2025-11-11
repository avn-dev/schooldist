<?php

/**
 * LDAD Connector class
 * old php4 style :-(
 */
class Ext_Ldap_Connector {
  var $hostname;

  var $cid = 0; // Connection ID
  var $bid = 0; // Bind ID
  
  var $result = 0;
  var $entries = 0;
  var $entry = 0;
  
  var $debugging=FALSE; // Debug output yes|no

  // Connects to LDAP-server defined by host
  // Uses default port (389)
  private function connect() {
    $this->cid = ldap_connect( $this->hostname );
    if(!$this->cid) {
      $this->error();
      return;
    }
    $this->debug('Created link to <i>'.$this->hostname
    .'</i> (not connected)');
  }

  // Attempts a named bind to the LDAP-server using
  // the distinguished name in $binddn and the password
  // in $passwd
  public function bind( $binddn, $passwd ) {
    $this->debug('Trying named bind as <i>'.$binddn.'</i>...');
    $this->bid = ldap_bind( $this->cid, $binddn, $passwd );
    if (! $this->bid ) {
		$this->error();
		return false;
    } else {
		return true;
    }
    $this->debug('Successfully bound to <i>'.$this->hostname
     .'</i> as user <i>'.$binddn.'</i> (connected)' );
  }

  // Attempts an anonmyous bind to the LDAP-server
  public function abind() {
    $this->debug('Trying anonymous bind...');
    $this->bid = ldap_bind( $this->cid );
    if( $this->bid ) {
      $this->debug('Successfully bound to <i>'
       .$this->hostname.'</i> as anonymous user (connected)');
    } else {
      $this->error();
      return;
    }
  }

  // closes the connection to the LDAP-server
  function unbind() {
    if( $this->bid <=0 || $this->cid <= 0) {
      $this->debug('While unbinding: Trying to unbind while'
      .'not bound/not connected');
      return;
    }
    $success = ldap_unbind( $this->cid );
    if (!$success) {
      $this->error();
      return;
    }
    $this->debug('Successfully unbound from <i>'
    .$this->hostname.'</i> (connection closed)');
  }

  // The Constructor: Connects and attempts an anonymous
  // bind. Uses default LDAP port.
  function __construct( $hostname, $debugging=FALSE ) {
    global $system_data;
  	
    if(\System::d('debugmode')) {
    	ob_start();
    	$this->debugging = TRUE;
    } else {
    	$this->debugging = FALSE;
    }

  	if (empty($hostname)) {
      $this->error('Can\'t instantiate LDAP_Connector without hostname info');
    }

    $this->debug('Constructing class LDAP_Connector (debug modus)');
    $this->hostname = $hostname;
    $this->connect();
    $this->abind();

  }

	// closes the connection
	function __destruct() {
		$this->debug('Destructing LDAP_Connector');
		$this->unbind();
	}

	// a error handling function
	protected function error($error="") {

		if ($this->debugging == TRUE) {

			echo '<p style="background:#FFAAAA"><b>';
		    if( empty( $error ) && ldap_errno( $this->cid ) ) {
				echo 'LDAP Error ('.ldap_errno($this->cid).') :'
					.ldap_err2str($this->cid).'</b><br>'
					.ldap_error($this->cid);
		    } else {
				echo 'An error occured:</b><br>'.$error;
		    }

		}

	}

	// the debug output
	protected function debug( $message ) {
		if ($this->debugging == TRUE) {
			echo '<p style="background:#AAAAFF">DEBUG (LDAP_Connector): '
				.$message.'</p>';
		}
	}

  // performs a simple search
  function search( $filter, $basedn )   {
    $this->debug('Performing search for <i>'
    .$filter.'</i> in directory <i>'.$basedn.'</i>');

    $this->result = ldap_search( $this->cid, $basedn, $filter );

    if (!$this->result) {
      $this->error();
    }
    return $this->result;
  }

  // returns the entries found by a previous search
  function getEntries() {
    $this->entries = ldap_get_entries( $this->cid, $this->result );
    if (!$this->entries) {
      $this->error();
    }
    return $this->entries;
  }

  // frees the results of previous search
  function freeResult() {
      $success = ldap_free_result( $this->result );
      if (!$success) $this->error();
  }
  
  // after having performed a search this method
  // needs to be called in order to retrieve the
  // first entry
  function firstEntry() {
    $this->entry = ldap_first_entry($this->cid, $this->result);
    return $this->entry;    
  }

  // returns the available attributes for the
  // selected entry
  function getAttributes() {
    return ldap_get_attributes($this->cid, $this->entry);
  }
  
  // returns an array with values for a given attribute
  function getValuesOf( $attribute ) {
    return ldap_get_values($this->cid, $this->entry, $attribute);
  }
  
  // returns the entries distinguished name
  function getEntryDN() {
    return ldap_get_dn($this->cid, $this->entry);
  }
  
  // selects the next entry or returns FALSE
  // if no further entry is available
  function nextEntry() {
   $this->entry = ldap_next_entry($this->cid, $this->entry);
   return $this->entry; 
  }

  // this one is called by the debugging mode implemented
  // in LDAP_Connector
  function getClass() { return 'Ext_Ldap_Connector'; }
  
}
