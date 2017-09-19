<?php
	namespace shanemcc\socket\iface;

	/**
	 * Class representing a Connection to/from a Socket.
	 *
	 * This allows us to read/write data to the socket.
	 */
	abstract class SocketConnection {
		/**
		 * Write some data to the connection and automatically add a "\n" to
		 * the end.
		 *
		 * @param  string ...$data Data to write
		 */
		public function writeln(String ...$data) {
			$this->write(...$data);
			$this->write("\n");
		}

		/**
		 * Write a some data to the connection.
		 *
		 * @param  string ...$data Data to write
		 */
		public abstract function write(String ...$data);

		/**
		 * Get remote address.
		 *
		 * @return string Remote socket address
		 */
		public abstract function getRemoteAddress(): ?String;

		/**
		 * Get local address.
		 *
		 * @return string Local socket address
		 */
		public abstract function getLocalAddress(): ?String;

		/**
		 * Close the socket.
		 */
		public abstract function close();
	}
