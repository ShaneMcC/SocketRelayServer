<?php
	namespace shanemcc\irc;

	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socket\iface\SocketConnection;

	use Evenement\EventEmitterInterface;
	use Evenement\EventEmitterTrait;

	/**
	 * IRC Socket Handler.
	 */
	class IRCSocketHandler extends BaseSocketHandler implements EventEmitterInterface {
		use EventEmitterTrait;

		/**
		 * Create a new IRCSocketHandler.
		 *
		 * @param SocketConnection $conn Client to handle
		 * @param IRCClient $client Client that created us.
		 * @param IRCConnectionSettings $settings Initial connection settings.
		 */
		public function __construct(SocketConnection $conn) {
			parent::__construct($conn);
		}

		/** {@inheritdoc} */
		public function onConnect() {
			$this->emit('socket.connected');
		}

		public function writeln(String $data) {
			$this->emit('data.out', [$data]);
			$this->getSocketConnection()->writeln($data);
		}

		/** {@inheritdoc} */
		public function onData(String $data) {
			$this->emit('data.in', [$data]);
		}

		/** {@inheritdoc} */
		public function onClose() {
			$this->emit('socket.closed');
		}

		/** {@inheritdoc} */
		public function onTimeout(): bool {
			return false;
		}
	}
