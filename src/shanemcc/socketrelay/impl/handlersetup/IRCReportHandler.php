<?php
	namespace shanemcc\socketrelay\impl\handlersetup;

	use shanemcc\socketrelay\iface\ReportHandlerSetup;
	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socket\iface\MessageLoop;
	use shanemcc\irc\IRCClient;
	use shanemcc\irc\IRCConnectionSettings;
	use shanemcc\socketrelay\impl\IRCReportHandler as IRCReportHandlerImpl;

	use \Throwable;

	class IRCReportHandler implements ReportHandlerSetup {
		/** {@inheritdoc} */
		public function setup(MessageLoop $loop, Array $clientConf): ReportHandler {
			$connectionSettings = $this->buildConnectionSettings($clientConf);
			$client = new IRCClient();

			if (isset($clientConf['handler']) && is_callable($clientConf['handler'])) {
				call_user_func($clientConf['handler'], $loop, $clientConf, $connectionSettings, $client);
			}

			$reconnectFunction = function($client) use ($connectionSettings, $loop) {
				$loop->schedule(30, false, function() use ($client, $connectionSettings) {
					$client->connect();
				});
			};
			$client->on('socket.closed', $reconnectFunction);
			$client->on('socket.connectfailed', $reconnectFunction);

			$queueSettings = @$newConfig['outputqueue'] ?: [];
			$client->setMessageLoop($loop)->setQueueSettings($queueSettings)->connect($connectionSettings);

			// Set up ReportHandler.
			return new IRCReportHandlerImpl($loop, $client);
		}

		/**
		 * Build an IRCConnectionSettings from a client config.
		 *
		 * @param Array $clientConf Config to build from
		 * @return IRCConnectionSettings based on $clientConfg
		 */
		private function buildConnectionSettings(Array $clientConf): IRCConnectionSettings {
			$connectionSettings = new IRCConnectionSettings();
			$connectionSettings->setNickname($clientConf['nickname']);
			$connectionSettings->setAltNickname($clientConf['altnickname']);
			$connectionSettings->setUsername($clientConf['username']);
			$connectionSettings->setRealname($clientConf['realname']);
			$connectionSettings->setHost($clientConf['host']);
			$connectionSettings->setPort($clientConf['port']);
			$connectionSettings->setPassword($clientConf['password']);
			$connectionSettings->setAutoJoin($clientConf['channels']);

			return $connectionSettings;
		}

		/** {@inheritdoc} */
		public function update(ReportHandler $reportHandler, Array $oldConfig, Array $newConfig) {
			$client = $reportHandler->getClient();

			$connectionSettings = $this->buildConnectionSettings($newConfig);
			$client->setConnectionSettings($connectionSettings);

			$queueSettings = @$newConfig['outputqueue'] ?: [];
			$client->setQueueSettings($queueSettings);
		}
	}
