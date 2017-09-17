<?php
	namespace shanemcc\socketrelayserver\impl\ReactSocket;

	use shanemcc\socketrelayserver\iface\ClientConnection as BaseClientConnection;
	use React\Socket\ConnectionInterface;

	/**
	 * React\Socket implementation of ClientConnection
	 */
	class ClientConnection extends BaseClientConnection {
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

		public function getRemoteAddress(): String {
			return $this->conn->getRemoteAddress();
		}

		public function getLocalAddress(): String {
			return $this->conn->getLocalAddress();
		}
	}
