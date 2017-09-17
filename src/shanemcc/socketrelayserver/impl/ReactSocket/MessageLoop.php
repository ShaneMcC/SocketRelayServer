<?php
	namespace shanemcc\socketrelayserver\impl\ReactSocket;
	use shanemcc\socketrelayserver\iface\MessageLoop as BaseMessageLoop;
	use shanemcc\socketrelayserver\iface\Socket as BaseSocket;

	use React\EventLoop\Factory as EventLoopFactory;
	use React\EventLoop\LoopInterface;


	/**
	 * ReactPHP Implementation of MessageLoop
	 *
	 * There should be only 1 instance of this.
	 */
	class MessageLoop extends BaseMessageLoop {
		/** @var LoopInterface Our LoopInterface */
		private $loop;

		/** @inheritDoc */
		public function __construct() {
			$this->loop = EventLoopFactory::create();
		}

		/** @inheritDoc */
		public function getSocket(String $host, int $port, int $timeout): BaseSocket {
			return new Socket($this, $host, $port, $timeout);
		}

		/** @inheritDoc */
		public function run() {
			$this->loop->run();
		}

		/** @inheritDoc */
		public function stop() {
			$this->loop->stop();
		}

		/** @inheritDoc */
		public function schedule(float $time, bool $repeat, Callable $function) {
			if ($repeat) {
				$this->loop->addPeriodicTimer($time, $function);
			} else if ($time == 0) {
				$this->loop->addTimer($function);
			} else {
				$this->loop->addTimer($time, $function);
			}
		}

		/**
		 * Get the underlying ReactPHP LoopInterface.
		 *
		 * @return LoopInterface ReactPHP LoopInterface.
		 */
		public function getLoopInterface(): LoopInterface {
			return $this->loop;
		}
	}
