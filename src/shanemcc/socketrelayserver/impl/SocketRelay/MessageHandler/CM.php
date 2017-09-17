<?php

	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;

	class CM extends TargettedMessage {
		/** @inheritDoc. */
		public function getMessageType(): String {
			return 'CM';
		}

		/** @inheritDoc. */
		public function getDescription(): String {
			return 'Send a message to a channel';
		}
	}
