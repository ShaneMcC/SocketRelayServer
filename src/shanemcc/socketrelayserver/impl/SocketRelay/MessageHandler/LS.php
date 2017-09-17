<?php

	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;
	use shanemcc\socketrelayserver\impl\SocketRelay\ServerSocketHandler;

	class LS extends MessageHandler {
		/** @inheritDoc. */
		public function getMessageType(): String {
			return 'LS';
		}

		/** @inheritDoc. */
		public function getDescription(): String {
			return 'List known message types';
		}

		/** @inheritDoc */
		public function handleMessage(ServerSocketHandler $handler, String $number, String $key, String $messageParams): bool {
			$handler->sendResponse($number, 'LS', '# Name -- Desc');
			foreach (ServerSocketHandler::getMessageHandlers() as $messageType => $messageHandler) {
				if ($handler->canAccess($key, $messageType)) {
					$handler->sendResponse($number, 'LS', $messageType . ' -- ' . $messageHandler['description']);
				}
			}

			return true;
		}

	}
