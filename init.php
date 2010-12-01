#!/usr/bin/php

<?php

/**
 * IRC trivia bot
 * Matthew Hallsworth <matthew.hallsworth@gmail.com>
 * PHP5.x required
 *
 * Based on http://www.irchelp.org/irchelp/rfc/rfc2812.txt for connection
 *
 * Setup your configuration in the variables below
 * ----------------------------------------------------------------------------
 */

$config = array(
'server' => "irc.lolliebag.com", 	# IRC server to connect to
'serverPassword' => "",				# Server password if required (default is blank)
'serverPort' => "6667",				# Port to connect to (default is 6667)
'destinationChannel' => "#trivia",  # Channel for bot to connect to
'username' => "trivbot",            # Username for the bot to have
'realname' => "PHP Trivia Bot",     # Realname for bot to have (IRC RFC2812 3.1.3)
'timeout' => 10, 					# Timeout in seconds for initializing connection
'logging' => 1,                     # 2 = all messages, 1 = only commands etc.
'modules' => array(                 # List of modules to load
	'parrot','math'
)
);

# Add full hostmasks here to add admins.
# ie. nickname!realname@hostaddress.isp.com

$adminList = array(
'lothar!lothar@110-174-74-30.static.tpgi.com.au',
);

/**
 * No need to edit below this line
 * ----------------------------------------------------------------------------
 */

include_once("class.ircBot.php");

set_time_limit(0); # Continual script setting for commandline execution
$bot = new ircBot($config, $adminList);
