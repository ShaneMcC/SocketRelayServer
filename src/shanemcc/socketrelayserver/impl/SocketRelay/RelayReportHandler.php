<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay;

	use shanemcc\socketrelayserver\SocketRelayClient;
	use shanemcc\socketrelayserver\iface\ReportHandler;
	use shanemcc\socketrelayserver\iface\SocketHandler as BaseSocketHandler;

	class RelayReportHandler implements ReportHandler {
		/** @var SocketRelayClient to relay reports to. */
		private $client;

		/**
		 * Create the ReportHandler.
		 *
		 * @param ?SocketRelayClient $client Client to relay reports to, or null
		 *                                   to discard.
		 */
		public function __construct(?SocketRelayClient $client) {
			$this->client = $client;
		}

		/** @inheritDoc */
		public function handle(BaseSocketHandler $handler, String $messageType, String $number, String $key, String $messageParams) {
			if ($this->client != null) {
				$this->client->addMessage($messageType . ' ' . $messageParams);
				$this->client->sendWithCallback(function() use ($handler, $number, $messageType) {
					if ($handler instanceof ServerSocketHandler) {
						$handler->sendResponse($number, $messageType, 'Message relayed.');
					}
				});
			} else {
				$handler->sendResponse($number, $messageType, 'Relaying disabled.');
			}
		}
	}
