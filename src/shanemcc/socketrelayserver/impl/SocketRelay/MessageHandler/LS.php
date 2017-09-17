<?php

	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;

	class LS extends MessageHandler {
		/** @inheritDoc. */
		public function getMessageType(): String {
			return 'LS';
		}

		/** @inheritDoc. */
		public function getDescription(): String {
			return 'Administration commands';
		}

		/** @inheritDoc */
		public function handleMessage(String $number, String $key, String $messageParams): bool {
			$this->getSocketHandler()->sendResponse($number, 'LS', '# Name -- Desc');
			foreach ($this->getSocketHandler()->getHandlers() as $messageType => $handler) {
				if ($this->getSocketHandler()->canAccess($key, $messageType)) {
					$this->getSocketHandler()->sendResponse($number, 'LS', $messageType . ' -- ' . $handler['description']);
				}
			}

			return true;
		}

	}
