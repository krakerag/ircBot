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
	 * Just repeat what was said to the bot with some funky rubbish
	 */
	function repeat($args) {
		return $args." SQUAWK!!";		
	}
}
