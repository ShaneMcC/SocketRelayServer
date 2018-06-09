<?php
	namespace shanemcc\irc\OutputQueue;

	use shanemcc\socket\iface\MessageLoop;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;

	/**
	 * Timed Bucket output queue.
	 *
	 * This queue implements a bucket system, we have a maximum capacity, and
	 * every line we send removes 1 from the current capacity.
	 *
	 * The bucket starts at maximum capacity.
	 *
	 * We have a timer that fires every timerRate seconds that increases the
	 * bucket capacity by refilRate, and attempts to send any pending lines.
	 *
	 * This allows us to burst when needed, but slows us down if we start
	 * sending too much.
	 */
	class TimedBucketOutputQueue extends OutputQueue {
		/** @var array Current queue of items waiting to send. */
		private $queue;

		/** @var float Current capacity of the bucket. */
		private $capacity = 0;

		/** @var float Maximum capacity of the bucket. */
		private $capacityMax = 5;

		/** @var float Refill rate of the bucket. */
		private $refilRate = 0.7;

		/** @var float Timer rate */
		private $timerRate = 1;

		/** @var bool Do we have an active timer currently? */
		private $hasTimer = false;

		/**
		 * Create a new OutputQueue
		 *
		 * @param MessageLoop $messageLoop Message loop that we use for scheduling.
		 * @param BaseSocketHandler $socket SocketHandler we write data to.
		 */
		public function __construct(MessageLoop $messageLoop, BaseSocketHandler $socket) {
			parent::__construct($messageLoop, $socket);
			$this->capacity = $this->capacityMax;
			$this->queue = new PriorityQueue();
		}

		/** {@inheritdoc} */
		public function writeln(String $line, int $priority = QueuePriority::Normal) {
			if ($priority == QueuePriority::Immediate) {
				$this->socket->writeln($line);
				$this->capacity--;
				return;
			} else {
				$this->queue->push($line, $priority);
			}

			$this->trySendLine();

			if (!$this->hasTimer) {
				$this->hasTimer = true;
				$this->messageLoop->schedule($this->timerRate, false, function() { $this->runTimer(); });
			}
		}

		/** {@inheritdoc} */
		public function clear() {
			$this->queue = new PriorityQueue();
		}

		/**
		 * Function called each time the timer fires.
		 *
		 * This will refill the bucket, and try and send any pending lines.
		 */
		private function runTimer() {
			$this->capacity = min($this->capacityMax, ($this->capacity + $this->refilRate));
			$this->trySendLine();

			if ($this->capacity < $this->capacityMax) {
				$this->hasTimer = true;
				$this->messageLoop->schedule($this->timerRate, false, function() { $this->runTimer(); });
			} else {
				$this->hasTimer = false;
			}
		}

		/**
		 * Try to send a single line.
		 *
		 * This will send a line if we have any lines capacity in our bucket.
		 *
		 * If not, it will do nothing.
		 *
		 * @param bool $empty Should we keep sending until the bucket is empty?
		 */
		private function trySendLine($empty = true) {
			if ($this->capacity >= 1 && $this->queue->count() > 0) {
				$this->socket->writeln($this->queue->pop());
				$this->capacity--;

				// Keep trying until we can't empty any further.
				if ($empty) { $this->trySendLine($empty); }
			}
		}

		/**
		 * Get current capacity
		 *
		 * @return float Current capacity
		 */
		public function getCapacity(): float {
			return $this->capacity;
		}

		/**
		 * Set current capacity
		 *
		 * @param float $newValue New Value
		 * @return $this For chaining.
		 */
		public function setCapacity(float $newValue): TimedBucketOutputQueue {
			$this->capacity = $newValue;
			return $this;
		}

		/**
		 * Get current maximum capacity
		 *
		 * @return float Current maximum capacity
		 */
		public function getCapacityMax(): float {
			return $this->capacityMax;
		}

		/**
		 * Set current maximum capacity
		 *
		 * @param float $newValue New Value
		 * @return $this For chaining.
		 */
		public function setCapacityMax(float $newValue): TimedBucketOutputQueue {
			$this->capacityMax = $newValue;
			return $this;
		}

		/**
		 * Get current refil rate
		 *
		 * @return float Current refil rate
		 */
		public function getRefilRate(): float {
			return $this->refilRate;
		}

		/**
		 * Set current refil rate
		 *
		 * @param float $newValue New Value
		 * @return $this For chaining.
		 */
		public function setRefilRate(float $newValue): TimedBucketOutputQueue {
			$this->refilRate = $newValue;
			return $this;
		}

		/**
		 * Get current timer rate
		 *
		 * @return float Current timer rate
		 */
		public function getTimerRate(): float {
			return $this->timerRate;
		}

		/**
		 * Set current timer rate
		 *
		 * @param float $newValue New Value
		 * @return $this For chaining.
		 */
		public function setTimerRate(float $newValue): TimedBucketOutputQueue {
			$this->timerRate = $newValue;
			return $this;
		}

		/** {@inheritdoc} */
		public function getPending(): Array {
			return $this->queue->getQueueItems();
		}
	}

