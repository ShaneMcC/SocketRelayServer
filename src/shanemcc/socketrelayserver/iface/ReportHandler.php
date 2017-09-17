<?php
	namespace shanemcc\socketrelayserver\iface;

	use shanemcc\socketrelayserver\iface\SocketHandler;

	/**
	 * Class to deal with handling a ClientConnection.
	 *
	 * Each new client is handled by a new instance of this class.
	 */
	interface ReportHandler {
		/**
		 * Called to handle a report.
		 */
		public function handle(SocketHandler $handler, String $messageType, String $number, String $key, String $messageParams);
	}
