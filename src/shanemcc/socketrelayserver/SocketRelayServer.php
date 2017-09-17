<?php
	namespace shanemcc\socketrelayserver;

	use shanemcc\socketrelayserver\impl\ReactSocket\SocketServer as React_Socket_SocketServer;
	use shanemcc\socketrelayserver\impl\SocketRelay\SocketHandlerFactory as SocketRelay_SocketHandlerFactory;
	use shanemcc\socketrelayserver\iface\ReportHandler;

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

		private function setSocketServer() {
			$this->server = new React_Socket_SocketServer($this->host, $this->port, $this->timeout);
			$this->server->setSocketHandlerFactory(new SocketRelay_SocketHandlerFactory($this));
		}

		public function getSocketServer() {
			return $this->server;
		}

		public function run() {
			$this->server->listen();
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
