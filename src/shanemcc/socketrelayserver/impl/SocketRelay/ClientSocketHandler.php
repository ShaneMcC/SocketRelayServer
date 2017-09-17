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
		/** @var SocketRelayClient Client that owns us. */
		private $client;

		/**
		 * Create a new ClientSocketHandler
		 *
		 * @param SocketConnection $conn Client to handle
		 * @param SocketRelayClient $client Client that owns us.
		 */
		public function __construct(SocketConnection $conn, SocketRelayClient $client) {
			parent::__construct($conn);
			$this->client = $client;
		}

		/**
		 * Get our Client.
		 *
		 * @return SocketRelayClient Client that owns us.
		 */
		public function getClient(): SocketRelayClient {
			return $this->client;
		}

		/** @inheritDoc */
		public function onConnect() {
			$i = 0;
			foreach ($this->client->getMessages() as $message) {
				$this->getSocketConnection()->writeln($i++ . ' ' . $this->client->getKey() . ' ' . $message);
			}
			$this->client->clearMessages();

			$this->getSocketConnection()->writeln('-- ' . $this->client->getKey() . ' Q');
			$this->getSocketConnection()->close();

			$this->client->messagesSent();
		}

	}
