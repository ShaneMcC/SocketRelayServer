<?php
	namespace shanemcc\socketrelayserver\iface;

	use shanemcc\socketrelayserver\iface\SocketHandler;

	/**
	 * Deal with sending a report somewhere.
	 */
	interface ReportHandler {
		/**
		 * Called to handle a report.
		 */
		public function handle(SocketHandler $handler, String $messageType, String $number, String $key, String $messageParams);
	}
