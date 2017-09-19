<?php
	namespace shanemcc\socketrelay\impl\messagehandler;

	use shanemcc\socketrelay\impl\ServerSocketHandler;

	class Q extends MessageHandler {
		/** {@inheritdoc}. */
		public function getMessageType(): String {
			return 'Q';
		}

		/** {@inheritdoc}. */
		public function getDescription(): String {
			return 'Close the connection';
		}

		/** {@inheritdoc} */
		public function handleMessage(ServerSocketHandler $handler, String $number, String $key, String $messageParams): bool {
			// Don't duplicate the close message if the user sends "-- <KEY> Q"
			if ($number != '--') {
				$handler->sendResponse($number, 'Sck', 'Closing Connection');
				$handler->getSocketConnection()->close();
			}

			return true;
		}

	}
