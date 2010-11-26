<?php

/**
 * ircModule class
 * This is a base class which all modules extend from
 * It sets up the framework for a module to have a batch of commands and those commands triggering
 * a batch of functions which are related to those commands, parsing the message as it
 * gets passed over, and causing reaction.
 *
 * Initialising an ircModule should receive a copy of the ircBot so communications can be
 * performed on the socket present.
 *
 */

class ircModule {

	protected $commands; # Array of commands the module supports

	/**
	 * Empty constructor which is overloaded in children
	 */
	function __construct() {
	}

	/**
	 * Determine if the command exists in the module
	 */
	function findCommand($requestedCommand) {
		foreach($this->commands as $command) {
			if($command == $requestedCommand) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Launch the requested command with the provided args
	 */
	function launch($command, $args) {
		if(method_exists($this, $command)) {
			return $this->$command($args);
		}
	}
}
