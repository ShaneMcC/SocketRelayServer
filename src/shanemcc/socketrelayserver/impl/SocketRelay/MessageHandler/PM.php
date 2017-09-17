<?php

	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;

	class PM extends TargettedMessage {
		/** @inheritDoc. */
		public function getMessageType(): String {
			return 'PM';
		}

		/** @inheritDoc. */
		public function getDescription(): String {
			return 'Send a message to a user';
		}
	}
