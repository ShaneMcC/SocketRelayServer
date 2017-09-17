<?php
	namespace shanemcc\socketrelayserver;

	use shanemcc\socketrelayserver\impl\ReactSocket\SocketServer as React_Socket_SocketServer;
	use shanemcc\socketrelayserver\impl\SocketRelay\SocketHandlerFactory as SocketRelay_SocketHandlerFactory;
	use shanemcc\socketrelayserver\impl\SocketRelay\SocketHandler as SocketRelay_SocketHandler;
	use shanemcc\socketrelayserver\iface\ReportHandler;
	use shanemcc\socketrelayserver\iface\SocketServer as BaseSocketServer;

	use shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler\MessageHandler;
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

		/** @var iface\SocketServer SocketServer we are using. */
		private $server;

		/** @var ReportHandler ReportHandler. */
		private $reportHandler;

		/** @var Array Array of valid keys. 'Key' => [Allowed Functions] */
		private $validKeys;

		/**
		 * Create a new SocketRelayServer
		 *
		 * @param String  $host Host to listen on.
		 * @param int $port Port to listen on.
		 * @param int $timeout Timeout for inactive connections.
		 */
		public function __construct(String $host, int $port, int $timeout) {
			$this->host = $host;
			$this->port = $port;
			$this->timeout = $timeout;
			$this->setSocketServer();
		}

		/**
		 * Set up the socket server.
		 */
		private function setSocketServer() {
			$this->server = new React_Socket_SocketServer($this->host, $this->port, $this->timeout);
			$this->server->setSocketHandlerFactory(new SocketRelay_SocketHandlerFactory($this));

			SocketRelay_SocketHandler::addMessageHandler(new A());
			SocketRelay_SocketHandler::addMessageHandler(new Q());
			SocketRelay_SocketHandler::addMessageHandler(new LS());
			SocketRelay_SocketHandler::addMessageHandler(new CM());
			SocketRelay_SocketHandler::addMessageHandler(new PM());
			SocketRelay_SocketHandler::addMessageHandler(new HELP());
		}


		/**
		 * Get our SocketServer
		 *
		 * @return SocketServer our SocketServer
		 */
		public function getSocketServer(): BaseSocketServer {
			return $this->server;
		}

		/**
		 * Run the socket server.
		 */
		public function run() {
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
