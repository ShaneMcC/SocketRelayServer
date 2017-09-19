<?php
	namespace shanemcc\socketrelayserver;

	use shanemcc\socketrelayserver\impl\SocketRelay\ServerSocketHandlerFactory as SocketRelay_ServerSocketHandlerFactory;
	use shanemcc\socketrelayserver\impl\SocketRelay\ServerSocketHandler as SocketRelay_ServerSocketHandler;
	use shanemcc\socket\iface\Socket as BaseSocket;
	use shanemcc\socket\iface\MessageLoop;

	use shanemcc\socketrelayserver\iface\ReportHandler;

	use shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler\Q;
	use shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler\CM;
	use shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler\A;
	use shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler\PM;
	use shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler\LS;
	use shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler\HELP;

	/**
	 * SocketRelayServer
	 */
	class SocketRelayServer {
		/** @var String Host to listen on. */
		private $host;

		/** @var int Port to listen on. */
		private $port;

		/** @var int Timeout for inactive connectons. */
		private $timeout;

		/** @var bool Are we running in verbose mode? */
		private $verbose;

		/** @var Socket Socket we are using. */
		private $server;

		/** @var MessageLoop MessageLoop that we are being run from. */
		private $messageLoop;

		/** @var ReportHandler ReportHandler. */
		private $reportHandler;

		/** @var Array Array of valid keys. 'Key' => [Allowed Functions] */
		private $validKeys;

		/**
		 * Create a new SocketRelayServer
		 *
		 * @param MessageLoop $loop MessageLoop we are being run from.
		 * @param String  $host Host to listen on.
		 * @param int $port Port to listen on.
		 * @param int $timeout Timeout for inactive connections.
		 */
		public function __construct(MessageLoop $loop, String $host, int $port, int $timeout) {
			$this->messageLoop = $loop;
			$this->host = $host;
			$this->port = $port;
			$this->timeout = $timeout;
			$this->setServerSocket();
		}

		/**
		 * Set up the server socket.
		 */
		private function setServerSocket() {
			$this->server = $this->messageLoop->getSocket($this->host, $this->port, $this->timeout);
			$this->server->setSocketHandlerFactory(new SocketRelay_ServerSocketHandlerFactory($this));

			SocketRelay_ServerSocketHandler::addMessageHandler(new A());
			SocketRelay_ServerSocketHandler::addMessageHandler(new Q());
			SocketRelay_ServerSocketHandler::addMessageHandler(new LS());
			SocketRelay_ServerSocketHandler::addMessageHandler(new CM());
			SocketRelay_ServerSocketHandler::addMessageHandler(new PM());
			SocketRelay_ServerSocketHandler::addMessageHandler(new HELP());
		}


		/**
		 * Get our Server Socket
		 *
		 * @return Socket our Server Socket
		 */
		public function getSocket(): BaseSocket {
			return $this->server;
		}

		/**
		 * Set the server to listen.
		 */
		public function listen() {
			if ($this->isVerbose()) { echo 'Begin listen server on: ', $this->host, ':', $this->port, "\n"; }
			$this->server->listen();
		}

		/**
		 * Set verbose mode.
		 *
		 * @param Bool $verbose New value for verbose mode.
		 */
		public function setVerbose(bool $verbose) {
			$this->verbose = $verbose;
		}

		/**
		 * Are we running in verbose mode?
		 *
		 * @return bool True iif verbose.
		 */
		public function isVerbose(): bool {
			return $this->verbose;
		}

		/**
		 * Set our valid keys.
		 *
		 * @param Array $validKeys Array of valid keys.
		 */
		public function setValidKeys(Array $validKeys) {
			$this->validKeys = $validKeys;
		}

		/**
		 * Get our valid keys.
		 *
		 * @return Array Array of valid keys.
		 */
		public function getValidKeys(): Array {
			return $this->validKeys;
		}

		/**
		 * Set our ReportHandler.
		 *
		 * @param ReportHandler $reportHandler Handler for reports.
		 */
		public function setReportHandler(ReportHandler $reportHandler) {
			$this->reportHandler = $reportHandler;
		}

		/**
		 * Get our ReportHandler.
		 *
		 * @return ReportHandler Handler for reports.
		 */
		public function getReportHandler(): ReportHandler {
			return $this->reportHandler;
		}
	}
