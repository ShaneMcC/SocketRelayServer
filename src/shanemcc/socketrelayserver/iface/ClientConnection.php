<?php
	namespace shanemcc\socketrelayserver\iface;

	/**
	 * Class representing a Connection from a Client.
	 *
	 * This allows us to read/write data to the client.
	 */
	abstract class ClientConnection {
		/**
		 * Write some data to the connection and automatically add a "\n" to
		 * the end.
		 *
		 * @param  String ...$data Data to write
		 */
		public function writeln(String ...$data) {
			$this->write(...$data);
			$this->write("\n");
		}

		/**
		 * Write a some data to the connection
		 *
		 * @param  String ...$data Data to write
		 */
		public abstract function write(String ...$data);

		/**
		 * Get remote address.
		 *
		 * @return String Remote socket address.
		 */
		public abstract function getRemoteAddress(): String;

		/**
		 * Get local address.
		 *
		 * @return String Local socket address.
		 */
		public abstract function getLocalAddress(): String;

		/**
		 * Close the socket.
		 */
		public abstract function close();
	}
