<?php
	namespace shanemcc\socket\impl\EchoTest;

	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;

	/**
	 * EchoTest SocketHandler.
	 */
	class SocketHandler extends BaseSocketHandler {
		/** {@inheritdoc} */
		public function onConnect() {
			echo '[', $this->getSocketID(), '] ', 'Client Connected.', "\n";
			$this->getSocketConnection()->writeln("Hello client!");
		}

		/** {@inheritdoc} */
		public function onData(String $data) {
			echo '[', $this->getSocketID(), '] ', 'Client Data: ', $data, "\n";
			$this->getSocketConnection()->writeln("You said: " . $data);
		}

		/** {@inheritdoc} */
		public function onClose() {
			echo '[', $this->getSocketID(), '] ', 'Client Closed.', "\n";
		}
	}
