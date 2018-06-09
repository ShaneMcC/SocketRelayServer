<?php
	namespace shanemcc\socketrelay\impl\messagehandler;

	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socketrelay\impl\ServerSocketHandler;

	class A extends MessageHandler {
		/** {@inheritdoc}. */
		public function getMessageType(): String {
			return 'A';
		}

		/** {@inheritdoc}. */
		public function getDescription(): String {
			return 'List known message types';
		}

		/** {@inheritdoc} */
		public function handleMessage(ServerSocketHandler $handler, String $number, String $key, String $messageParams): bool {
			$messageBits = explode(' ', $messageParams);

			$messageBits[0] = strtoupper($messageBits[0]);

			if ($messageBits[0] == 'RAW') {
				$reportHandler = $handler->getServer()->getReportHandler();

				if ($reportHandler instanceof ReportHandler) {
					$reportHandler->handle($handler, 'A', $number, $key, implode(' ', $messageBits));
					return true;
				}
			} else if ($messageBits[0] == 'REHASH') {
				reloadConfig();

				return true;
			} else if ($messageBits[0] == 'KILL') {
				$reason = isset($messageBits[1]) ? $messageBits[1] : 'Server closing.';
				$socket = $handler->getServer()->getSocket();
				$socket->close($reason);

				// Give sockets time to clear their write buffer before we exit.
				$socket->getMessageLoop()->schedule(1, false, function() use ($socket) {
					$socket->getMessageLoop()->stop();
				});
				return true;
			}

			return false;
		}

	}
