<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay;

	use shanemcc\socketrelayserver\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socketrelayserver\iface\SocketConnection;
	use shanemcc\socketrelayserver\SocketRelayClient;
	use shanemcc\socketrelayserver\iface\ReportHandler;

	/**
	 * SocketRelay ClientSocketHandler.
	 */
	class ClientSocketHandler extends BaseSocketHandler {
		/** @var String Key for sending message. */
		private $key;

		/** @var Array Array of messages to send. */
		private $messages;

		/** @var ?Callable Callable to call when we are done. */
		private $success;

		/**
		 * Create a new ClientSocketHandler
		 *
		 * @param SocketConnection $conn Client to handle
		 * @param Array $messages Array of messages to send.
		 * @param ?Callable $success Callable to call when we are done.
		 */
		public function __construct(SocketConnection $conn, String $key, Array $messages, ?Callable $success = null) {
			parent::__construct($conn);
			$this->key = $key;
			$this->messages = $messages;
			$this->success = $success;
		}

		/** @inheritDoc */
		public function onConnect() {
			$i = 0;
			foreach ($this->messages as $message) {
				if (is_string($message)) {
					$this->getSocketConnection()->writeln($i++ . ' ' . $this->key . ' ' . $message);
				} else if (is_callable($message)) {
					call_user_func_array($message, [$this->getSocketConnection(), &$i, $this->key]);
				}
			}
			$this->getSocketConnection()->writeln('-- ' . $this->key . ' Q');
			$this->getSocketConnection()->close();

			if ($this->success != null) {
				call_user_func($this->success);
			}
		}

	}
