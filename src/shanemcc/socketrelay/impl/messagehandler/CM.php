<?php
	namespace shanemcc\socketrelay\impl\messagehandler;

	class CM extends TargetedMessage {
		/** {@inheritdoc}. */
		public function getMessageType(): String {
			return 'CM';
		}

		/** {@inheritdoc}. */
		public function getDescription(): String {
			return 'Send a message to a channel';
		}
	}
