<?php
	namespace shanemcc\irc\OutputQueue;

	class ImmediateOutputQueue extends OutputQueue {
		/** {@inheritdoc} */
		public function writeln(String $line) {
			$this->socket->writeln($line);
		}

		/** {@inheritdoc} */
		public function getPending(): Array {
			return [];
		}
	}
