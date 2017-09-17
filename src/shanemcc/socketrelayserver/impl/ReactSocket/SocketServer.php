<?php
	namespace shanemcc\socketrelayserver\impl\ReactSocket;

	use React\EventLoop\Factory as EventLoopFactory;
	use React\EventLoop\LoopInterface;

	use React\Socket\TcpServer;
	use React\Socket\ConnectionInterface;

	use shanemcc\socketrelayserver\iface\SocketServer as BaseSocketServer;
	use shanemcc\socketrelayserver\impl\ReactSocket\ClientConnection;

	/**
	 * SocketServer Implemenation using ReactPHP library.
	 */
	class SocketServer extends BaseSocketServer {
		/** @var ConcertoSocketServer Underlying SocketServer */
		private $server;

		/** @var LoopInterface Event Loop handler. */
		private $loop;

		/** @var Array of open handlers. */
		private $handlers;

		/** @var bool Are we accepting new connections? */
		private $allowNew = false;

		/** @inheritDoc */
		public function listen() {
			if ($this->loop !== null) { throw new Exception('Already Listening.'); }
			$this->allowNew = true;

 			$this->handlers = new \SplObjectStorage();

			$this->loop = EventLoopFactory::create();
			$this->server = new TcpServer($this->getHost() . ':' . $this->getPort(), $this->loop);

			$this->server->on('connection', function(ConnectionInterface $conn) {
				$clientConnection = new ClientConnection($conn);
				$handler = $this->getSocketHandlerFactory()->get($clientConnection);
				$this->handlers[$handler] = ['time' => time(), 'conn' => $clientConnection];

				if (!$this->allowNew) {
					$handler->sendResponse('--', 'Sck', 'Closing Connection.');
					$clientConnection->close();
					return;
				}

				try { $handler->onConnect(); } catch (Throwable $ex) { $this->onError('connect', $ex); }

				$conn->on('data', function (String $data) use ($handler) {
					try { $handler->onData(trim($data)); } catch (Throwable $ex) { $this->onError('data', $ex); }

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
			});

			$this->loop->addPeriodicTimer($this->getTimeout(), function() {
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

			$this->loop->run();
		}

		/**
		 * Display exception information.
		 *
		 * @param String $handlerName Handler name.
		 * @param Throwable $throwable The exception.
		 */
		public function onError(String $handlerName, Throwable $throwable) {
			echo 'Throwable in ', $handlerName, ' handler.', "\n";
			echo "\t", $throwable->getMessage(), "\n";
			foreach ($throwable->getTrace() as $t) {
				echo "\t\t", $t, "\n";
			}
		}

		/** @inheritDoc */
		public function close(String $message = 'Server closing.') {
			// Stop accepting any new sockets.
			$this->allowNew = false;
			$this->server->pause();

			// Close sockets.
			foreach ($this->handlers as $handler) {
				$handler->sendResponse('--', 'Sck', 'Closing Connection - ' . $message);
				$this->handlers[$handler]['conn']->close();
				unset($this->handlers[$handler]);
			}

			// Give sockets time to clear their write buffer before we exit.
			$this->loop->addPeriodicTimer(5, function() {
				$this->loop->stop();
			});
		}
	}
