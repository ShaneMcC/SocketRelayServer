<?php
	namespace shanemcc\socketrelay\impl\handlersetup;

	use shanemcc\socketrelay\iface\ReportHandlerSetup;
	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socket\iface\MessageLoop;
	use shanemcc\discord\DiscordClient;
	use shanemcc\socketrelay\impl\DiscordHandler as DiscordHandlerImpl;

	use \Throwable;

	class DiscordHandler implements ReportHandlerSetup {
		/** {@inheritdoc} */
		public function setup(MessageLoop $loop, Array $clientConf): ReportHandler {
			$client = new DiscordClient($clientConf['clientid'], $clientConf['clientSecret'], $clientConf['token']);

			echo 'Discord invite link: https://discordapp.com/oauth2/authorize?client_id=' . $clientConf['clientid'] . '&scope=bot&permissions=536931328', "\n";

			$client->setDebug($clientConf['debug']);
			$client->setLoopInterface($loop->getLoopInterface())->connect();

			// Set up ReportHandler.
			return new DiscordHandlerImpl($loop, $client);
		}

		/** {@inheritdoc} */
		public function update(ReportHandler $reportHandler, Array $oldConfig, Array $newConfig) {
			$changeClient = false;
			if ($newConfig['clientid'] != $oldConfig['clientid']) { $changeClient = true; }
			if ($newConfig['clientSecret'] != $oldConfig['clientSecret']) { $changeClient = true; }
			if ($newConfig['token'] != $oldConfig['token']) { $changeClient = true; }

			$client = $reportHandler->getClient();

			if ($changeClient) {
				$oldClient = $client;
				$client->disconnect();

				$client = new DiscordClient($newConfig['clientid'], $newConfig['clientSecret'], $newConfig['token']);
				$client->setLoopInterface($oldClient->getMessageLoop()->getLoopInterface())->connect();
				$reportHandler->setClient($client);

				echo 'Updated discord invite link: https://discordapp.com/oauth2/authorize?client_id=' . $clientConf['clientid'] . '&scope=bot&permissions=536931328', "\n";
			}

			$client->setDebug($newConfig['debug']);
		}
	}
