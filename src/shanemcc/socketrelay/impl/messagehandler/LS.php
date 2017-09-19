<?php
	namespace shanemcc\socketrelay\impl\messagehandler;

	use shanemcc\socketrelay\impl\ServerSocketHandler;

	class LS extends MessageHandler {
		/** {@inheritdoc}. */
		public function getMessageType(): String {
			return 'LS';
		}

		/** {@inheritdoc}. */
		public function getDescription(): String {
			return 'List known message types';
		}

		/** {@inheritdoc} */
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
