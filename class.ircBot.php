<?php 

/**
 * ircBot class
 * Manages the main IRC connection and flow control between client and server.
 * From here, other classes get called in as required.
 */

class ircBot {

	private $socket; # Socket variable
	private $config; # Config from object construction
	private $admins; # Admin list of hostmasks
	private $ready; # A toggle to let controller know the bot is ready for commands

	public $inbound; # Last inbound message from server
	public $lastMessage; # Parsed array of the last message from server

	/**
	 * Setup a connection and init monitoring of chat
	 */
	function __construct($config, $adminList) {
		$this->config = $config;
		$this->admins = $adminList;
		if($this->config['serverPassword'] == "") $this->config['serverPassword'] = "NOPASS";
		$this->log("triviaBot - starting up...");
		$this->log("[INIT]: ".print_r($config,true));

		# Generate a new connection
		try {
			$this->connectToServer($config);
		} catch (Exception $e) {
			die("Exception caught, terminating. Could not connect to server: ".$e->getMessage());
		}

		# Should now have a connection in $this->socket, lets initialise
		try {
			$this->sendCommand("PASS ".$this->config['serverPassword']."\n\r");
			$this->sendCommand("NICK ".$this->config['username']."\n\r");
			$this->sendCommand("USER ".$this->config['username']." 0 * :".$this->config['realname']."\n\r");
		} catch (Exception $e) {
			die($this->log("Exception caught while negotiating server join: ".$e->getMessage()));
		}

		# Now pass off control to the main controller
		$this->controller();
	}
	
	/**
	 * Server controller, passes off tasks as required from the input
	 */
	private function controller() {
		# While connected, manage the input
		while(!feof($this->socket)) {
			# Read inbound connection into $this->inbound
			$this->getTransmission();

			# Detect MOTD (message number 376 or 422)
			if(strpos($this->inbound, $this->config['server']." 376") || strpos($this->inbound, $this->config['server']." 422")) {
				# Join channel then...
				$this->sendCommand("JOIN ".$this->config['destinationChannel']."\n\r");
            }
			# If successfully joined the channel, mark bot as ready (names list message id 353)
			if(strpos($this->inbound, $this->config['server']." 353")) {
				$this->ready = true;
            }

			if($this->ready) {
				# Parse the inbound message and scan for a command
				$this->parseMessage();

			}

			# If server has sent PING command, handle
            if(substr($this->inbound, 0, 6) == "PING :") {
				# Reply with PONG for keepalive
				$this->sendCommand("PONG :".substr($this->inbound, 6)."\n\r");
            }
		}
	}

	/**
	 * Parse the inbound message by splitting it into components
	 */
	private function parseMessage() {
		# Regexp to parse a formatted message
		# The message should come in format of
		# :(.*)\!(.*)\@(.*) PRIVMSG #channelname \:\%(.*) (.*) for a command, or
		# :(.*)\!(.*)\@(.*) PRIVMSG #channelname \:(.*) for regular chatter
		# thus returning nickname, realname, hostmask, (command, arguments) || (chatter)
		$matched = false;
		$pattern = '/:(.*)\!(.*)\@(.*) PRIVMSG '.$this->config['destinationChannel'].' \:\%([^ ]*) (.*)/';
		preg_match($pattern, $this->inbound, $matches);
		if(count($matches) > 1) {
			$matched = true;
			$this->lastMessage = array(
				'nickname' => $matches[1],
				'realname' => $matches[2],
				'hostname' => $matches[3],
				'command' => $matches[4],
				'args' => $matches[5],
				'chatter' => ''
			);
		}

		if(!$matched) {
			$pattern = '/:(.*)\!(.*)\@(.*) PRIVMSG '.$this->config['destinationChannel'].' \:(.*)/';
			preg_match($pattern, $this->inbound, $matches);
			if(count($matches) > 1) {
				$this->lastMessage = array(
					'nickname' => $matches[1],
					'realname' => $matches[2],
					'hostname' => $matches[3],
					'command' => '',
					'args' => '',
					'chatter' => $matches[4]
				);
			}
		}
	}

	/**
	 * Log the message (output to command line at the moment)
	 * TODO: extend this to log to a file in /var/log at some point
	 */
	private function log($message) {
		echo $message."\n";
	}

	/**
	 * Create a new connection, and setup the socket
	 */
	private function connectToServer($config) {
		$this->socket = fsockopen($config['server'], $config['serverPort'], $errno, $errstr, $config['timeout']);
		if(!$this->socket) {
			throw new Exception("$errstr ($errno)");
		}
	}

	/**
	 * Read in a line from the server
	 */
	private function getTransmission() {
		$this->inbound = fgets($this->socket, 1024);
		# Log the inbound message
		$this->log("[RECV]: ".rtrim($this->inbound,"\r\n"));
	}

	/**
	 * Send a command to the remote server
	 */
	private function sendCommand($command) {
		# Log the outbound message
		$this->log("[SEND]: ".rtrim($command,"\r\n"));
		$writtenBytes = fwrite($this->socket, $command, strlen($command));
		if($writtenBytes < strlen($command)) {
			throw new Exception("Attempted to write ".strlen($command)." bytes, could only send {$writtenBytes} to socket");
		}
	}
}
