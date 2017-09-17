<?php
	namespace shanemcc\socketrelayserver\impl\EchoTest;

	use shanemcc\socketrelayserver\iface\SocketHandler as BaseSocketHandler;

	/**
	 * EchoTest SocketHandler.
	 */
	class SocketHandler extends BaseSocketHandler {
		/** @inheritDoc */
		public function onConnect() {
			echo '[', $this->getSocketID(), '] ', 'Client Connected.', "\n";
			$this->getSocketConnection()->writeln("Hello client!");
		}

		/** @inheritDoc */
		public function onData(String $data) {
			echo '[', $this->getSocketID(), '] ', 'Client Data: ', $data, "\n";
			$this->getSocketConnection()->writeln("You said: " . $data);
		}

		/** @inheritDoc */
		public function onClose() {
			echo '[', $this->getSocketID(), '] ', 'Client Closed.', "\n";
		}
	}
