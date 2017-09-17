<?php
	namespace shanemcc\socketrelayserver\impl\ReactSocket;

	use React\EventLoop\Factory as EventLoopFactory;
	use React\EventLoop\LoopInterface;

	use React\Socket\Server;
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

		/** @inheritDoc */
		public function listen() {
			if ($this->loop !== null) { throw new Exception('Already Listening.'); }

			$this->loop = EventLoopFactory::create();
			$this->server = new Server('tcp://' . $this->getHost() . ':' . $this->getPort(), $this->loop);

			$this->server->on('connection', function(ConnectionInterface $conn) {
				$handler = $this->getSocketHandlerFactory()->get(new ClientConnection($conn));
				$handler->onConnect();

				$conn->on('data', function (String $data) use ($handler) {
					$handler->onData(trim($data));
				});

				$conn->on('close', function () use ($handler) {
					$handler->onClose();
				});
			});

			$this->loop->run();
		}
	}
