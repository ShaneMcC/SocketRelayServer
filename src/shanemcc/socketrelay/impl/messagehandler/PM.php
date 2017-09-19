<?php
	namespace shanemcc\socketrelay\impl\messagehandler;

	class PM extends TargetedMessage {
		/** {@inheritdoc}. */
		public function getMessageType(): String {
			return 'PM';
		}

		/** {@inheritdoc}. */
		public function getDescription(): String {
			return 'Send a message to a user';
		}
	}
