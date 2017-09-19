<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;

	class CM extends TargettedMessage {
		/** {@inheritdoc}. */
		public function getMessageType(): String {
			return 'CM';
		}

		/** {@inheritdoc}. */
		public function getDescription(): String {
			return 'Send a message to a channel';
		}
	}
