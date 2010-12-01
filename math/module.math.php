<?php

/**
 * Module: math
 * Simple math commands caught by a simple %eq command modifier.
 * I'd like to find a way to just have %= be the command, but i don't know
 * if php handles something like 'function =()' :)
 * We can elaborate upon it later on for more functionality
 *
 */

class math extends ircModule {

	/**
	 * Constructor to establish command list (required)
	 */
	function __construct() {
		$this->commands = array(
			'eq',
		);
	}

	/**
	 * NOTE:
	 * $parsedArgs should be of the format:
	 * $parsedArgs = array(
				'nickname' => user's nick who called command
				'realname' => user's realname who called command
				'hostname' => user's hostmask as known by server
				'command'  => the command (same as function name)
				'args'     => arguments post command
				'chatter'  => should be empty!

	/**
	 * Basic math based on the args and the operator in the middle
	 */
	function eq($parsedArgs) {
		if($this->help($parsedArgs)) {
			# Return some help text
			return $parsedArgs['nickname'].": Use %eq to generate math results. i.e. '%eq 7 * 6' or '%eq 81 / 7' etc.";
		}
		$return = $this->_matheval($parsedArgs['args']);
		return $parsedArgs['nickname'].": ".$return;
	}

	/**
	 * Operational function to eval a math string
	 * Shamelessly pinched from: http://www.php.net/manual/en/function.eval.php#92603
	 */
	function _matheval($equation) {

	    $equation = preg_replace("/[^0-9+\-.*\/()%]/","",$equation);
	    $equation = preg_replace("/([+-])([0-9]{1})(%)/","*(1\$1.0\$2)",$equation);
	    $equation = preg_replace("/([+-])([0-9]+)(%)/","*(1\$1.\$2)",$equation);
	    $equation = preg_replace("/([0-9]+)(%)/",".\$1",$equation);

	    if($equation == "") {
			$return = 0;
		} else {
			eval("\$return=" . $equation . ";" );
    	}
    	return $return;
	}
}
