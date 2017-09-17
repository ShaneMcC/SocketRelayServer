<?php

	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;

	class Q extends MessageHandler {
		/** @inheritDoc. */
		public function getMessageType(): String {
			return 'Q';
		}

		/** @inheritDoc. */
		public function getDescription(): String {
			return 'Close the connection';
		}

		/** @inheritDoc */
		public function handleMessage(String $number, String $key, String $messageParams): bool {
			// Don't duplicate the close message if the user sends "-- <KEY> Q"
			if ($number != '--') {
				$this->getSocketHandler()->sendResponse($number, 'Sck', 'Closing Connection');
				$this->getSocketHandler()->getClientConnection()->close();
			}

			return true;
		}

	}
