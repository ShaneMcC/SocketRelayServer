<?php
	namespace shanemcc\irc\OutputQueue;

	use \Countable;

	class PriorityQueue implements Countable {
		private $queue = [];

		public function push($item, int $priority) {
			if (!isset($this->queue[$priority])) { $this->queue[$priority] = []; }

			$this->queue[$priority][] = $item;
		}

		public function pop() {
			foreach (array_keys($this->queue) as $priority) {
				$result = array_shift($this->queue[$priority]);

				if (empty($this->queue[$priority])) { unset($this->queue[$priority]); }

				return $result;
			}

			return NULL;
		}

		public function count() {
			$result = 0;
			foreach (array_keys($this->queue) as $priority) {
				$result += count($this->queue[$priority]);
			}

			return $result;
		}

		public function getQueueItems(): Array {
			$items = [];
			foreach ($this->queue as $priority => $lines) {
				foreach ($lines as $line) {
					$items[] = [$line, $priority];
				}
			}

			return $items;
		}
	}
