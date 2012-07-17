<?php

/**
 * Module: parrot
 * This is a basic module to demonstrate the idea of an ircBot module
 * They have a simple set of functions which can be bound to a command set
 *
 */

class parrot extends ircModule {

	/**
	 * Constructor to establish command list (required)
	 */
	function __construct() {
		$this->commands = array(
			'repeat', 'inverse'
		);

        $this->triggers = array(); // no triggers
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
	 * Just repeat what was said to the bot with some funky rubbish
	 */
	function repeat($parsedArgs) {
		if($this->help($parsedArgs)) {
			# Return some help text
			return $parsedArgs['nickname'].": Use botname: repeat <stuff> to just echo out whatever text you provide to the function.";
		}
		return $parsedArgs['nickname'].": ".$parsedArgs['args']." SQUAWK!!";
	}

	/**
	 * Inverse the chatter to demonstrate that we just don't have to return
	 * normal text. Basically any module can work on a command and args and do
	 * processing, and return a textual result. This result can be formatted
	 * as well using IRC formatting if required
	 */
	function inverse($parsedArgs) {
		if($this->help($parsedArgs)) {
			# Return some help text
			return $parsedArgs['nickname'].": Use botname: inverse <stuff> to echo out the reverse of what text you have provided.";
		}
		return $parsedArgs['nickname'].": ".strrev($parsedArgs['args']);
	}

}
