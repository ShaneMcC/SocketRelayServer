<?php
	namespace shanemcc\socketrelay\impl\handlersetup;

	use shanemcc\socketrelay\iface\ReportHandlerSetup;
	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socket\iface\MessageLoop;
	use shanemcc\socketrelay\SocketRelayClient;
	use shanemcc\socketrelay\impl\RelayReportHandler;

	use \Throwable;

	class SocketRelay implements ReportHandlerSetup {
		/** {@inheritdoc} */
		public function setup(MessageLoop $loop, Array $clientConf): ReportHandler {
			$client = new SocketRelayClient($loop, $clientConf['host'], $clientConf['port'], 5, $clientConf['key']);

			return new RelayReportHandler($loop, $client, $clientConf['suffix']);
		}

		/** {@inheritdoc} */
		public function update(ReportHandler $reportHandler, Array $oldConfig, Array $newConfig) {
			$client = new SocketRelayClient($loop, $newConfig['host'], $newConfig['port'], 5, $newConfig['key']);
			$reportHandler->setClient($client);
		}
	}
