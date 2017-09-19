<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;

	use shanemcc\socketrelayserver\impl\SocketRelay\ServerSocketHandler;

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
		public function handleMessage(ServerSocketHandler $handler, String $number, String $key, String $messageParams): bool {
			// Don't duplicate the close message if the user sends "-- <KEY> Q"
			if ($number != '--') {
				$handler->sendResponse($number, 'Sck', 'Closing Connection');
				$handler->getSocketConnection()->close();
			}

			return true;
		}

	}
