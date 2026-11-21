<?php
	namespace shanemcc\socketrelay\impl\handlersetup;

	use shanemcc\socketrelay\iface\ReportHandlerSetup;
	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socket\iface\MessageLoop;
	use shanemcc\socket\impl\ReactSocket\MessageLoop as ReactMessageLoop;
	use shanemcc\socketrelay\impl\DiscordHandler as DiscordHandlerImpl;

	use Discord\Discord;
	use Discord\WebSockets\Intents;
	use Monolog\Handler\StreamHandler;
	use Monolog\Level;
	use Monolog\Logger;
	use React\EventLoop\LoopInterface;

	class DiscordHandler implements ReportHandlerSetup {
		/** @var int Permissions to request in the invite link (View Channels + Send Messages). */
		const PERMISSIONS = 3072;

		/** {@inheritdoc} */
		public function setup(MessageLoop $loop, Array $clientConf): ReportHandler {
			if (!($loop instanceof ReactMessageLoop)) { die('Discord requires ReactPHP MessageLoop.' . "\n"); }

			$client = $this->createClient($loop->getLoopInterface(), $clientConf);

			// Set up ReportHandler.
			return new DiscordHandlerImpl($loop, $client);
		}

		/**
		 * Create and connect a new Discord client.
		 *
		 * @param LoopInterface $loop Loop to run the client on.
		 * @param Array $clientConf Client config.
		 * @return Discord New client.
		 */
		private function createClient(LoopInterface $loop, Array $clientConf): Discord {
			$logger = new Logger('discord');
			$logger->pushHandler(new StreamHandler('php://stdout', $clientConf['debug'] ? Level::Debug : Level::Info));

			$client = new Discord(['token' => $clientConf['token'],
			                       'loop' => $loop,
			                       'logger' => $logger,
			                       'intents' => Intents::GUILDS,
			                       'disableVoiceClient' => true,
			                      ]);

			$client->on('application-init', function(Discord $discord) {
				echo 'Discord invite link: ', $discord->application->getInviteURLAttribute(self::PERMISSIONS), "\n";
			});

			return $client;
		}

		/** {@inheritdoc} */
		public function update(ReportHandler $reportHandler, Array $oldConfig, Array $newConfig) {
			if ($newConfig['token'] == $oldConfig['token'] && $newConfig['debug'] == $oldConfig['debug']) { return; }

			$oldClient = $reportHandler->getClient();
			$oldClient->close(false);

			$reportHandler->setClient($this->createClient($oldClient->getLoop(), $newConfig));
		}
	}
