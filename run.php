#!/usr/bin/php
<?php
	require_once(__DIR__ . '/functions.php');

	use shanemcc\socketrelayserver\SocketRelayServer;
	use shanemcc\socketrelayserver\iface\MessageLoop as BaseMessageLoop;
	use shanemcc\socketrelayserver\iface\ReportHandler;
	use shanemcc\socketrelayserver\iface\SocketConnection;
	use shanemcc\socketrelayserver\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socketrelayserver\iface\SocketHandlerFactory as BaseSocketHandlerFactory;
	use shanemcc\socketrelayserver\impl\SocketRelay\SocketHandler as SocketRelaySocketHandler;

	use shanemcc\socketrelayserver\impl\ReactSocket\MessageLoop as React_MessageLoop;

	// TODO: This should be nicer.
	class RelayReportHandler implements ReportHandler {
		/** @var Array Array of config. */
		private $config;

		/** @var BaseMessageLoop Message Loop for sockets. */
		private $loop;

		/**
		 * Create the ReportHandler.
		 *
		 * @param Array $config Array Array of config.
		 * @param BaseMessageLoop $messageLoop Message Loop.
		 */
		public function __construct(Array $config, BaseMessageLoop $messageLoop) {
			$this->config = $config;
			$this->loop = $messageLoop;
		}

		/** @inheritDoc */
		public function handle(BaseSocketHandler $handler, String $messageType, String $number, String $key, String $messageParams) {
			$reportHandler = $this->config['reporthandler'];
			$config = isset($this->config['reporter'][$reportHandler]) ? $this->config['reporter'][$reportHandler] : [];

			if ($reportHandler == 'socketrelay') {

				$clientSocket = $this->loop->getSocket($config['host'], $config['port'], 30);

				// TODO: This is fucking horrible.
				//       In short, we create lots of anonymous bits, just so
				//       that we can relay the message somewhere else.
				//       FML.
				$factory = new class($config, $number, $messageType, $messageParams, $handler) implements BaseSocketHandlerFactory {
					private $params;
					public function __construct(...$params) { $this->params = $params; }
					public function get(SocketConnection $conn) : BaseSocketHandler {
						return new class($conn, ...$this->params) extends BaseSocketHandler {
							private $params;
							public function __construct($conn, ...$params) { parent::__construct($conn); $this->params = $params; }

							public function onConnect() {
								list($config, $number, $messageType, $messageParams, $handler) = $this->params;

								$this->getSocketConnection()->writeln('-- ' . $config['key'] . ' ' . $messageType . ' ' . $messageParams);

								if ($handler instanceof SocketRelaySocketHandler) {
									$handler->sendResponse($number, $messageType, 'Message relayed.');
								}

								$this->getSocketConnection()->close();
							}
						};
					}
				};

				$clientSocket->setSocketHandlerFactory($factory);
				$clientSocket->connect();
			}
		}
	}

	$loop = new React_MessageLoop();

	$server = new SocketRelayServer($loop, $config['listen']['host'], (int)$config['listen']['port'], (int)$config['listen']['timeout']);
	$server->setValidKeys($config['validKeys']);
	$server->setReportHandler(new RelayReportHandler($config, $loop));
	$server->setVerbose($config['verbose']);
	$server->listen();

	$loop->run();
