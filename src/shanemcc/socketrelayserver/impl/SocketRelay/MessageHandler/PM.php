<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;

	class PM extends TargettedMessage {
		/** {@inheritdoc}. */
		public function getMessageType(): String {
			return 'PM';
		}

		/** {@inheritdoc}. */
		public function getDescription(): String {
			return 'Send a message to a user';
		}
	}
