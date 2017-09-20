<?php
	namespace shanemcc\socket\impl\ReactSocket;

	use React\Socket\TcpServer;
	use React\Socket\Connector;
	use React\Socket\TimeoutConnector;
	use React\Socket\ConnectionInterface;

	use shanemcc\socket\iface\Socket as BaseSocket;

	use \Throwable;
	use \Exception;

	/**
	 * Socket Implemenation using ReactPHP library.
	 */
	class Socket extends BaseSocket {
		/** @var object Underlying Socket */
		private $socket;

		/** @var array of open handlers. */
		private $handlers;

		/** @var bool Are we accepting new connections? */
		private $allowNew = false;

		/** {@inheritdoc} */
		public function __construct(MessageLoop $loop, String $host, int $port, int $timeout) {
			parent::__construct($loop, $host, $port, $timeout);
			$this->handlers = new \SplObjectStorage();
		}

		/** {@inheritdoc} */
		public function listen() {
			if ($this->socket !== null) { throw new Exception('Socket is already active.'); }
			$this->allowNew = true;

			if ($this->getMessageLoop() instanceof MessageLoop) {
				$this->socket = new TcpServer($this->getHost() . ':' . $this->getPort(), $this->getMessageLoop()->getLoopInterface());
			} else {
				throw new Exception('Invalid MessageLoop');
			}

			$this->socket->on('connection', [$this, 'handleConnection']);

			$this->setTimers();
		}

		/** {@inheritdoc} */
		public function connect($connectTimeout = 5) {
			if ($this->socket !== null) { throw new Exception('Socket is already active.'); }
			$this->allowNew = true;

 			if ($this->getMessageLoop() instanceof MessageLoop) {
 				$loop = $this->getMessageLoop()->getLoopInterface();
				$this->socket = new TimeoutConnector(new Connector($loop), $connectTimeout, $loop);

				$this->socket->connect($this->getHost() . ':' . $this->getPort())->then([$this, 'handleConnection'], function (Throwable $error) {
					$this->onError('connectattempt', $error);
				});

			} else {
				throw new Exception('Invalid MessageLoop');
			}

			$this->setTimers();
		}

		public function handleConnection(ConnectionInterface $conn) {
			$SocketConnection = new SocketConnection($conn);
			$handler = $this->getSocketHandlerFactory()->get($SocketConnection);
			$this->handlers[$handler] = ['time' => time(), 'conn' => $SocketConnection];

			if (!$this->allowNew) {
				try { $handler->onConnectRefused(); } catch (Throwable $ex) { $this->onError('connect', $ex); }
				$SocketConnection->close();
				return;
			}

			try { $handler->onConnect(); } catch (Throwable $ex) { $this->onError('connect', $ex); }

			$conn->on('data', function (String $data) use ($handler) {
				$data = explode("\n", trim($data));
				foreach ($data as $d) {
					try { $handler->onData(trim($d)); } catch (Throwable $ex) { $this->onError('data', $ex); }
				}

				if ($this->handlers->contains($handler)) {
					$data = $this->handlers[$handler];
					$data['time'] = time();
					$this->handlers[$handler] = $data;
				}
			});

			$conn->on('close', function () use ($handler) {
				try { $handler->onClose(); } catch (Throwable $ex) { $this->onError('close', $ex); }
				unset($this->handlers[$handler]);
			});
		}

		/**
		 * Start idle-timeout timer.
		 */
		protected function setTimers() {
			if ($this->getTimeout() <= 0) { return; }

			$this->getMessageLoop()->schedule($this->getTimeout(), true, function() {
				$timeout = time() - $this->getTimeout();

				$killed = [];

				foreach ($this->handlers as $handler) {
					$time = $this->handlers[$handler]['time'];

					if ($time < $timeout) {
						$close = true;
						try { $close = $handler->onTimeout(); } catch (Throwable $ex) { $this->onError('timeout', $ex); }

						if ($close) {
							$killed[] = $handler;
						} else {
							$this->handlers[$handler] = time();
						}
					}
				}

				foreach ($killed as $handler) {
					$this->handlers[$handler]['conn']->close();
					unset($this->handlers[$handler]);
				}
			});
		}

		/** {@inheritdoc} */
		public function close(String $message = 'Socket closing.') {
			// Stop accepting any new sockets.
			$this->allowNew = false;

			// Close sockets.
			foreach ($this->handlers as $handler) {
				$handler->closeSocket($message);
				$this->handlers[$handler]['conn']->close();
				unset($this->handlers[$handler]);
			}
		}
	}
