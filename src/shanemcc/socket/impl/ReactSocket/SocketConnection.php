<?php
	namespace shanemcc\socket\impl\ReactSocket;

	use shanemcc\socket\iface\SocketConnection as BaseSocketConnection;
	use React\Socket\ConnectionInterface;

	/**
	 * React\Socket implementation of SocketConnection
	 */
	class SocketConnection extends BaseSocketConnection {
		/** @var ConnectionInterface Connection object. */
		private $conn;

		public function __construct(ConnectionInterface $conn) {
			$this->conn = $conn;
		}

		public function write(String ...$str) {
			foreach ($str as $s) {
				$this->conn->write($s);
			}
		}

		public function getRemoteAddress(): ?String {
			return $this->conn->getRemoteAddress();
		}

		public function getLocalAddress(): ?String {
			return $this->conn->getLocalAddress();
		}

		public function close() {
			$this->conn->end();
		}
	}
