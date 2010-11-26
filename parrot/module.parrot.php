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
			'repeat'
		);
	}

	/**
	 * Just repeat what was said to the bot
	 */
	function repeat($args) {

		/**
		 * TODO:
		 * So I'm at a crossroads here
		 * This function is a child of ircModule. Neither are related to
		 * ircBot at all - yet I want to use the sendMessage argument in
		 * the ircBot class so that all messages outbound are logged. As well as
		 * using the ircBot socket which is the active connection to the server
		 *
		 * Issues... need to fix.
		 *
		 ? ircBot::sendMessage($args);
		 */
	}
}
