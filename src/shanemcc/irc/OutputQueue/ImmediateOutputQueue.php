<?php
	namespace shanemcc\irc\OutputQueue;

	class ImmediateOutputQueue extends OutputQueue {
		/** {@inheritdoc} */
		public function writeln(String $line, int $priority = QueuePriority::Normal) {
			$this->socket->writeln($line);
		}

		/** {@inheritdoc} */
		public function getPending(): Array {
			return [];
		}

		/** {@inheritdoc} */
		public function clear() { }
	}
