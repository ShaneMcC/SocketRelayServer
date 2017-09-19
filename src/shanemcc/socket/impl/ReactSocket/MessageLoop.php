<?php
	namespace shanemcc\socket\impl\ReactSocket;

	use shanemcc\socket\iface\MessageLoop as BaseMessageLoop;
	use shanemcc\socket\iface\Socket as BaseSocket;

	use React\EventLoop\Factory as EventLoopFactory;
	use React\EventLoop\LoopInterface;


	/**
	 * ReactPHP Implementation of MessageLoop.
	 *
	 * There should be only 1 instance of this.
	 */
	class MessageLoop extends BaseMessageLoop {
		/** @var LoopInterface Our LoopInterface */
		private $loop;

		/** {@inheritdoc} */
		public function __construct() {
			$this->loop = EventLoopFactory::create();
		}

		/** {@inheritdoc} */
		public function getSocket(String $host, int $port, int $timeout): BaseSocket {
			return new Socket($this, $host, $port, $timeout);
		}

		/** {@inheritdoc} */
		public function run() {
			$this->loop->run();
		}

		/** {@inheritdoc} */
		public function stop() {
			$this->loop->stop();
		}

		/** {@inheritdoc} */
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
		 * @return LoopInterface ReactPHP LoopInterface
		 */
		public function getLoopInterface(): LoopInterface {
			return $this->loop;
		}
	}
