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

	public $modules; # Array holding active modules & their commands
	public $inbound; # Last inbound message from server
	public $lastMessage; # Parsed array of the last message from server

	/**
	 * Setup a connection and init monitoring of chat
	 */
	function __construct($config, $adminList) {
		$this->config = $config;
		$this->admins = $adminList;
		if($this->config['serverPassword'] == "") $this->config['serverPassword'] = "NOPASS";
		$this->log("ircBot - starting up...\n");

		# Load modules
		require_once("class.ircModule.php");
		$this->log("[INIT]: Loading modules...");
		foreach($config['modules'] as $module) {
			$this->loadModule($module);
		}
		$this->log("[INIT]: Module load finished");

		# Generate a new connection
		try {
			$this->connectToServer($config);
		} catch (Exception $e) {
			die("Exception caught, terminating. Could not connect to server: ".$e->getMessage());
		}

		# Should now have a connection in $this->socket, lets initialise
		try {
			$this->sendCommand("PASS ".$this->config['serverPassword']);
			$this->sendCommand("NICK ".$this->config['username']);
			$this->sendCommand("USER ".$this->config['username']." 0 * :".$this->config['realname']);
		} catch (Exception $e) {
            /** @var $this ircBot */
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
				$this->sendCommand("JOIN ".$this->config['destinationChannel']);
				$this->log("[INIT]: Joined destination channel");
            }
			# If successfully joined the channel, mark bot as ready (names list message id 353)
			if(strpos($this->inbound, $this->config['server']." 353")) {
				$this->ready = true;
				$this->log("[INIT]: Ready for commands!");
            }

			if($this->ready) {
				# Parse the inbound message and scan for a command
				$this->parseMessage();

                # See if this command can be found in the command list
                # Command list is established by the loaded modules
				if(strlen($this->lastMessage['command']) > 0) {
					foreach($this->modules as $moduleName => $moduleObj) {
						if($moduleObj->findCommand($this->lastMessage['command'])) {
                            # Launch command
							$this->log(" -> Found command '".$this->lastMessage['command']."' in module '".$moduleName."' called by user: '".$this->lastMessage['nickname']."'");
							$reply = $this->modules[$moduleName]->launch($this->lastMessage['command'], $this->lastMessage);
							if(strlen($reply) > 0) {
								$this->sendMessage($reply);
							}
						}
					}
				}

                # If not, scan chatter for relevant calls to the bot
                if(strlen($this->lastMessage['chatter']) > 0) {
                    foreach($this->modules as $moduleName => $moduleObj) {
                        if($trigger = $moduleObj->findTrigger($this->lastMessage['chatter'])) {
                            # Found a trigger for a command, fire it off
                            # First, strip out chatter into command and args
                            $triggered_command = $moduleObj->getCommandByTrigger($trigger);
                            $this->lastMessage['command'] = $triggered_command;
                            $chatter = $this->lastMessage['chatter'];
                            $this->lastMessage['chatter'] = '';
                            $this->lastMessage['args'] = rtrim(ltrim($chatter,$trigger),"\n\r");
                            # Launch command
                            $this->log(" -> Found command '".$this->lastMessage['command']."' in module '".$moduleName."' by trigger '".$trigger."' called by user: '".$this->lastMessage['nickname']."'");
                            $reply = $this->modules[$moduleName]->launch($this->lastMessage['command'], $this->lastMessage);
                            if(strlen($reply) > 0) {
                                $this->sendMessage($reply);
                            }
                        }
                    }
                }
			}

			# If server has sent PING command, handle
            if(substr($this->inbound, 0, 6) == "PING :") {
				# Reply with PONG for keepalive
				$this->sendCommand("PONG :".substr($this->inbound, 6));
            }
            
            $this->lastMessage = null;
            $this->inbound = null;
		}
	}

	/**
	 * Parse the inbound message by splitting it into components
	 */
	private function parseMessage() {

		# Regexp to parse a formatted message
		# The message should come in format of
		# :(.*)\!(.*)\@(.*) PRIVMSG #channelname \:(.*) for regular chatter
		# thus returning nickname, realname, hostmask, (command, arguments) || (botname: command (arguments)) || (chatter)
		$matched = false;

        # Try to match against the bots name!
        # This could mean it is a direct message in chan followed by a command request
        if(!$matched) {
            $pattern = '/:(.*)\!(.*)\@(.*) PRIVMSG '.$this->config['destinationChannel'].' \:('.$this->config['username'].')\:(?: +)?([^\s]*) (.*)/';
            preg_match($pattern, $this->inbound, $matches);
            if(count($matches) > 1) {
                $matched = true;
                $this->lastMessage = array(
                    'nickname' => $matches[1],
                    'realname' => $matches[2],
                    'hostname' => $matches[3],
                    'command' => $matches[5],
                    'args' => rtrim($matches[6],"\n\r"),
                    'chatter' => ''
                );
                # Have to match a case whereby a command of 'botname: command' with no args is provided (for help)
                # If this case occurs we have to do some arg movement otherwise the command parser won't get a hit
                if($this->lastMessage['args'] != "" && $this->lastMessage['command'] == '') {
                    $this->lastMessage['command'] = $this->lastMessage['args'];
                    $this->lastMessage['args'] = "";
                }
            }
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
					'chatter' => rtrim($matches[4],"\n\r")
				);
			}
		}
	}

    /**
     * Load up a module into the parent holder
     * @param $module
     */
    private function loadModule($module) {

		require_once("$module/module.$module.php");

		try {
			# Create a new object of required module, and send a reference to current object
			# so that it can append it's own command set to the current object
			$this->modules[$module] = new $module();
			if($this->modules[$module] instanceof $module) {
				$this->log(" -> Module loaded: $module");
			}
		} catch (Exception $e) {
			$this->log("Exception caught while attempting to load module '$module': ".$e->getMessage());
		}
	}

	/**
	 * Log the message (output to command line at the moment)
	 * TODO: extend this to log to a file in /var/log at some point in time
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
		if($this->config['logging'] == 2) {
			$this->log("[RECV]: ".rtrim($this->inbound,"\r\n"));
		}
	}

	/**
	 * Send a command to the remote server
	 */
	private function sendCommand($command) {
		# Log the outbound message
		if($this->config['logging'] == 2) {
			$this->log("[SEND]: ".rtrim($command,"\r\n"));
		}
		if(substr($command,-4) != "\n\r") {
			$command = $command . "\n\r";
		}
		$writtenBytes = fwrite($this->socket, $command, strlen($command));
		if($writtenBytes < strlen($command)) {
			throw new Exception("Attempted to write ".strlen($command)." bytes, could only send {$writtenBytes} to socket");
		}
	}

	/**
	 * Send a message to the channel
	 */
	public function sendMessage($message) {
		$this->sendCommand("PRIVMSG ".$this->config['destinationChannel']." :".$message);
	}
}
