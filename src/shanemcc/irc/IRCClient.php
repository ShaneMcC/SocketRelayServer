<?php
	namespace shanemcc\irc;

	use shanemcc\socket\iface\SocketHandlerFactory as BaseSocketHandlerFactory;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socket\iface\SocketConnection;
	use shanemcc\socket\iface\Socket as BaseSocket;
	use shanemcc\socket\iface\MessageLoop;

	use shanemcc\irc\OutputQueue\OutputQueue;
	use shanemcc\irc\OutputQueue\TimedBucketOutputQueue;
	use shanemcc\irc\OutputQueue\ImmediateOutputQueue;
	use shanemcc\irc\OutputQueue\QueuePriority;

	use Evenement\EventEmitter;

	use \Exception;
	use \Throwable;

	/**
	 * IRCClient.
	 */
	class IRCClient extends EventEmitter {
		/** @var BaseSocket Socket that is connected. */
		private $socket;

		/** @var MessageLoop MessageLoop that we are being run from. */
		private $messageLoop;

		/** @var IRCSocketHandler Socket handler for our socket. */
		private $handler;

		/** @var EventEmitter Internal event emitter. */
		private $internalEmitter;

		private $got001 = false;
		private $post005 = false;
		private $postMOTD = false;
		private $serverInformationLines = [];
		private $lastDataTime = 0;
		private $sentPing = false;

		private $myNickname = '';
		private $connectionSettings = [];
		private $queueSettings = [];
		private $connectTime = 0;

		private $outputQueue;

		private $enableDebugging = false;

		/**
		 * Create a new IRCClient.
		 */
		public function __construct() {
			$this->internalEmitter = new EventEmitter();

			$this->internalEmitter->on('data.in.tokens', [$this, 'processLine']);

			$this->internalEmitter->on('raw.001', [$this, 'process001']);
			$this->internalEmitter->on('raw.005.after', [$this, 'post005']);

			$this->internalEmitter->on('raw.433', [$this, 'nickInUse']);

			$this->internalEmitter->on('raw.376', [$this, 'afterMOTD']);
			$this->internalEmitter->on('raw.422', [$this, 'afterMOTD']);

			$this->internalEmitter->on('process.nick', [$this, 'processNICK']);
		}

		/**
		 * Set the message loop to use for the socket.
		 *
		 * @param MessageLoop $messageLoop
		 * @return self
		 */
		public function setMessageLoop(MessageLoop $messageLoop) {
			if ($this->socket !== null) { throw new Exception('Already connected.'); }
			$this->messageLoop = $messageLoop;

			return $this;
		}

		/**
		 * Get the message loop being used by this client.
		 *
		 * @return MessageLoop our messageloop
		 */
		public function getMessageLoop(): MessageLoop {
			return $this->messageLoop;
		}

		public function getEnableDebugging(): bool {
			return $this->enableDebugging;
		}

		public function setEnableDebugging(bool $newValue) {
			$this->enableDebugging = ($newValue == true);
			return $this;
		}

		public function getConnectionSettings(): IRCConnectionSettings {
			return $this->connectionSettings;
		}

		public function setConnectionSettings(?IRCConnectionSettings $connectionSettings) {
			$oldSettings = $this->connectionSettings;
			$this->connectionSettings = $connectionSettings;

			if ($this->outputQueue === null) { return; }

			// Update nickname.
			$nick = $this->connectionSettings->getNickname();
			$this->writeln(sprintf('NICK %s', $nick));

			// Reconnect if server changed.
			$reconnectRequired = false;

			if ($connectionSettings->getHost() != $oldSettings->getHost()) { $reconnectRequired = true; }
			if ($connectionSettings->getPort() != $oldSettings->getPort()) { $reconnectRequired = true; }
			if ($connectionSettings->getPassword() != $oldSettings->getPassword()) { $reconnectRequired = true; }
			if ($connectionSettings->getUsername() != $oldSettings->getUsername()) { $reconnectRequired = true; }
			if ($connectionSettings->getRealname() != $oldSettings->getRealname()) { $reconnectRequired = true; }

			if ($reconnectRequired) {
				$this->writeln(sprintf('QUIT :Server details changed, reconnecting.'));
			} else {
				$old = explode(',', $oldSettings->getAutoJoin());
				$new = explode(',', $connectionSettings->getAutoJoin());

				$removed = array_diff($old, $new);
				$added = array_diff($new, $old);

				if (!empty($removed)) {
					$this->leaveChannel(implode(',', $removed), 'Channel removed from config.');
				}

				if (!empty($added)) {
					$this->joinChannel(implode(',', $added));
				}
			}

			return $this;
		}

		/**
		 * Connect to IRC.
		 *
		 * @param Callable $error Callback if there is an error connecting.
		 */
		public function connect(IRCConnectionSettings $connectionSettings = null) {
			if ($this->socket !== null) { throw new Exception('Already connected.'); }

			$this->reset();
			$this->connectTime = time();
			if ($connectionSettings != null) {
				$this->connectionSettings = $connectionSettings;
			} else {
				$connectionSettings = $this->connectionSettings;
			}
			if (!($this->connectionSettings instanceof IRCConnectionSettings)) {
				throw new Exception('No known ConnectionSettings');
			}

			$this->myNickname = $connectionSettings->getNickname();

			$startLoop = false;
			if ($this->messageLoop == null) {
				$startLoop = true;
				$this->messageLoop = new \shanemcc\socket\impl\ReactSocket\MessageLoop();
			}

			$socket = $this->messageLoop->getSocket($connectionSettings->getHost(), $connectionSettings->getPort(), -1);
			$socket->setSocketHandlerFactory(new class($this) implements BaseSocketHandlerFactory {
				private $client;
				public function __construct(IRCClient $client) { $this->client = $client; }
				public function get(SocketConnection $conn) : BaseSocketHandler {
					return $this->client->setSocketHandler(new IRCSocketHandler($conn));
				}
			});

			$socket->setErrorHandler(function(String $handlerName, Throwable $throwable) use ($startLoop) {
				if ($handlerName == 'connectattempt' || $handlerName == 'timeout') {
					$this->reset();

					if (!empty($this->listeners('socket.connectfailed'))) {
						$this->doEmit('socket.connectfailed', [$throwable]);
					} else {
						$this->showThrowable($throwable);
					}

					if ($startLoop) { $this->messageLoop->stop(); }
				} else {
					$this->showThrowable($throwable);
				}
			});

			$this->socket = $socket;
			$socket->connect();

			$pingTimeoutTime = 60;

			$this->messageLoop->schedule($pingTimeoutTime, true, function($timer) use ($socket, $pingTimeoutTime) {
				if ($this->socket != $socket) {
					// We are the wrong timer, abort.
					echo 'Cancelling self.', "\n";
					$this->messageLoop->cancel($timer);
				} else {
					if ($this->lastDataTime < time() - $pingTimeoutTime) {
						// We haven't had data in a while.
						// Try to send a ping if we haven't already, otherwise
						// close the socket.
						if ($this->sentPing) {
							$this->quit('Ping Timeout.');
							$this->socket->close();
						} else {
							$this->writeln('PING ' . time(), QueuePriority::Immediate);
							$this->lastDataTime = time();
							$this->sentPing = true;
						}
					}
				}
			});

			if ($startLoop) { $this->messageLoop->run(); }
		}

		private function reset() {
			$this->outputQueue = null;
			$this->socket = null;

			if ($this->handler !== null) { $this->handler->removeAllListeners(); }
			$this->handler = null;

			$this->got001 = false;
			$this->isReady = false;
			$this->post005 = false;
			$this->postMOTD = false;
			$this->serverInformationLines = [];
			$this->lastDataTime = time();
			$this->sentPing = false;
		}

		/**
		 * Set our socket handler
		 *
		 * @param IRCSocketHandler $handler New handler.
		 * @return self
		 */
		public function setSocketHandler(IRCSocketHandler $handler) {
			if ($this->handler !== null) { throw new Exception('Handler already assigned.'); }
			$this->handler = $handler;
			$this->updateOutputQueue();

			$this->handler->on('socket.connected', [$this, 'socketConnected']);
			$this->handler->on('data.out', [$this, 'dataOut']);
			$this->handler->on('data.in', [$this, 'dataIn']);
			$this->handler->on('socket.closed', [$this, 'socketClosed']);

			return $this->handler;
		}

		public function getQueueSettings(): Array {
			return $this->queueSettings;
		}

		public function setQueueSettings(?Array $queueSettings) {
			$this->queueSettings = $queueSettings;
			if ($this->outputQueue !== null) { $this->updateOutputQueue(); }
			return $this;
		}

		private function updateOutputQueue() {
			$oldQueue = $this->outputQueue;
			$queueChanged = false;

			$config = $this->queueSettings;
			if ($config == null) { $config = []; }

			$config['enabled'] = @$config['enabled'] ?: true;

			if ($config['enabled']) {
				$config['capacityMax'] = @$config['capacityMax'] ?: 5;
				$config['refilRate'] = @$config['refilRate'] ?: 0.7;
				$config['timerRate'] = @$config['timerRate'] ?: 1;
				$config['bytesPerUnit'] = @$config['bytesPerUnit'] ?: 1;
				$config['enableDebugging'] = @$config['enableDebugging'] ?: false;

				if ($oldQueue === null || !($oldQueue instanceof TimedBucketOutputQueue)) {
					$queueChanged = ($oldQueue !== null);
					$this->outputQueue = new TimedBucketOutputQueue($this->messageLoop, $this->handler);
				}

				$this->outputQueue->setCapacityMax($config['capacityMax']);
				$this->outputQueue->setRefilRate($config['refilRate']);
				$this->outputQueue->setTimerRate($config['timerRate']);
				$this->outputQueue->setBytesPerUnit($config['bytesPerUnit']);
				$this->outputQueue->setEnableDebugging($config['enableDebugging']);
			} else {
				if ($oldQueue === null || !($oldQueue instanceof ImmediateOutputQueue)) {
					$queueChanged = ($oldQueue !== null);
					$this->outputQueue = new ImmediateOutputQueue($this->messageLoop, $this->handler);
				}
			}

			if ($queueChanged) {
				foreach ($oldQueue->getPending() as $item) {
					$this->outputQueue->writeln($item[0], $item[1]);
				}
				$oldQueue->clear();
				$oldQueue = null;
			}
		}

		private function doEmit(String $event, array $params = []) {
			// Internal Emitter for our own events that users can't
			// add/remove things on.
			try {
				$this->internalEmitter->emit($event, $params);
			} catch (Throwable $t) { $this->showThrowable($t); }

			// The public emitter includes a reference to us as the first
			// param.
			try {
				array_unshift($params, $this);
				$this->emit($event, $params);
			} catch (Throwable $t) { $this->showThrowable($t); }
		}

		public function socketConnected() {
			$nick = $this->connectionSettings->getNickname();
			$user = $this->connectionSettings->getUsername();
			$localhost = parse_url($this->handler->getSocketConnection()->getLocalAddress(), PHP_URL_HOST);
			$remotehost = $this->connectionSettings->getHost();
			$real = $this->connectionSettings->getRealname();
			$pass = $this->connectionSettings->getPassword();

			if (!empty($pass)) {
				$this->writeln(sprintf('PASS %s', $pass));
			}
			$this->writeln(sprintf('NICK %s', $nick));
			$this->writeln(sprintf('USER %s %s %s :%s', $user, $localhost, $remotehost, $real));

			$this->doEmit('socket.connected');
		}

		public function socketClosed() {
			$this->reset();
			$this->doEmit('socket.closed');
		}

		public function writeln(String $line, int $priority = QueuePriority::Normal) {
			if ($this->outputQueue === null) { throw new Exception('No open socket.'); }
			$this->outputQueue->writeln($line, $priority);
		}

		public function dataOut(String $data) {
			if ($this->enableDebugging) { echo '[', date('r'), ']   OUT: ', $data, "\n"; }
			$this->doEmit('data.out', [$data]);
		}

		public function dataIn(String $data) {
			if ($this->enableDebugging) { echo '[', date('r'), ']    IN: ', $data, "\n"; }

			$this->doEmit('data.in', [$data]);
			$bits = $this->tokenizeLine($data);
			$this->doEmit('data.in.tokens', [$bits]);
		}

		private function tokenizeLine(String $line): array {
			$last = explode(' :', $line, 2);
			$bits = explode(' ', $last[0]);

			if (isset($last[1])) { $bits[] = $last[1]; }

			return $bits;
		}

		// Based on https://github.com/DMDirc/Parser/blob/master/irc/src/main/java/com/dmdirc/parser/irc/IRCParser.java#L1125;
		public function processLine(array $bits) {
			if (!isset($bits[1])) { return; }
			$this->lastDataTime = time();
			$this->sentPing = false;

			$first = strtoupper($bits[0]);
			$param = strtoupper($bits[1]);

			if ($first == 'PING') {
				$this->writeln(sprintf('PONG :%s', $param), QueuePriority::Immediate);
			} else if ($param == 'PING' && isset($bits[2])) {
				$this->writeln(sprintf('PONG :%s', $bits[2]), QueuePriority::Immediate);
			} else if ($first == 'PONG') {
				$this->sentPing = false;
			} else if ($first == 'ERROR') {
				$this->internalEmitter->emit('server.error', [$bits]);
			} else {

				$nParam = is_numeric($param) ? (int)$param : -1;

				if ($this->got001) {
					if ($first == 'NOTICE' || (isset($bits[2]) && strtoupper($bits[2]) == 'NOTICE')) {
						$this->doEmit('auth.notice', [$bits]);
					}

					if (!$this->post005) {
   						if ($nParam < 0 || $nParam > 5) {
                            $this->doEmit('raw.005.after', [$bits]);
                        } else {
                        	$serverInformationLines[] = $bits;
                        }
                    }

					if (is_numeric($param)) {
						$this->doEmit(sprintf('raw.%03d', (int)$param), [$bits]);
					} else {
						$this->doEmit(sprintf('process.%s', strtolower($param)), [$bits]);
					}
				} else {
					switch ($nParam) {
						case 1:
							$serverInformationLines[] = $bits;
							// Fallthrough
						case 433: // Nickname in use
						case 464: // Password Missmatch
							$this->doEmit(sprintf('raw.%03d', (int)$param), [$bits]);
							break;
						default:
							if ($param == "NICK") {
								// Swallow undesired pre-001 NICK messages.
							} else {
								$this->doEmit('auth.notice', [$bits]);
							}
					}

				}
			}
		}

		/**
		 * Display exception information.
		 *
		 * @param Throwable $throwable The exception
		 */
		public function showThrowable(Throwable $throwable) {
			echo 'Throwable caught.', "\n";
			echo "\t", $throwable->getMessage(), "\n";
			foreach (explode("\n", $throwable->getTraceAsString()) as $t) {
				echo "\t\t", $t, "\n";
			}
		}

		public function process001(array $bits) {
			$this->got001 = true;
			$this->myNickname = $bits[2];

			$this->doEmit('server.ready');

			$channels = $this->getConnectionSettings()->getAutoJoin();
			$this->getMessageLoop()->schedule(5, false, function() use ($channels) {
				try {
					$this->joinChannel($channels);
				} catch (Exception $e) { }
			});
		}

		public function isReady(): bool {
			return ($this->handler !== null && $this->outputQueue !== null && $this->socket !== null && $this->got001);
		}

		public function post005(array $bits) {
			$this->post005 = true;
		}

		public function afterMOTD(array $bits) {
			$this->doEmit('motd.after');
		}

		public function nickInUse(array $bits) {
			// We only care before 001.
			if (!$this->got001) {
				// Try using altnick first if it is not the same as the normal
				// nickname.
				if ($this->myNickname == $this->connectionSettings->getNickname() && $this->myNickname != $this->connectionSettings->getAltNickname()) {
					$this->myNickname = $this->connectionSettings->getAltNickname();
					$this->setNickname($this->myNickname);
				} else {
					// Else try adding ` to the end of the nick repeatedly.
					$this->myNickname .= '`';
					$this->setNickname($this->myNickname);
				}
			}
		}

		public function processNICK(array $bits) {
			$changedPerson = explode('!', explode(':', $bits[0], 2)[1], 1)[0];

			if ($changedPerson == $this->myNickname && isset($bits[2])) {
				$this->myNickname = $bits[2];
			}
		}

		public function getNickname() {
			return $this->myNickname;
		}

		public function setNickname(String $wantedNick) {
			$this->writeln(sprintf('NICK %s', $wantedNick));
		}

		public function validChannel(String $target): bool {
			return TRUE;
		}

		public function validUser(String $target): bool {
			return TRUE;
		}

		public function sendMessage(String $target, String $message) {
			$this->writeln(sprintf('PRIVMSG %s :%s', $target, $message));
		}

		public function joinChannel(String $channel) {
			$this->writeln(sprintf('JOIN %s', $channel));
		}

		public function leaveChannel(String $channel, String $reason = '') {
			$this->writeln(sprintf('PART %s :%s', $channel, $reason));
		}

		public function quit(String $reason = '') {
			$this->outputQueue->clear();
			$this->writeln(sprintf('QUIT :%s', $reason));
		}

	}
