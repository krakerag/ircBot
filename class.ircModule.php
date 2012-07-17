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

    protected $triggers; # A hash map of triggers in chat that will call a command in the module

	/**
	 * Empty constructor which is overloaded in children
	 */
	function __construct() {
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
       )
     */

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
     * See if we have a matched trigger in the provided chatter to fire off a command via the trigger map
     */
    function findTrigger($chatter) {
        foreach($this->triggers as $trigger => $matching_command) {
            if(substr($chatter,0,strlen($trigger)) == $trigger) {
                # Matching trigger
                return $trigger;
            }
        }
        return false;
    }

    /**
     * Return the command matching the trigger if it exists
     */
    function getCommandByTrigger($trigger) {
        if(is_array($this->triggers)) {
            return $this->triggers[$trigger];
        }
        return false;
    }

    /**
	 * Launch the requested command with the provided args
	 */
	function launch($command, $parsedArgs) {
		if(method_exists($this, $command)) {
			return $this->$command($parsedArgs);
		}
	}

	/**
	 * Scan the arguments to determine a help string (or if there are no args to a command, to help out)
	 */
	function help($parsedArgs) {
        if(is_string($parsedArgs['args']) && (strlen($parsedArgs['args']) == 0 || $parsedArgs['args'] == 'help')) {
            return true;
        }
		if(is_array($parsedArgs['args']) && $parsedArgs['args'][0] == 'help') {
			return true;
		}
		return false;
	}
}
