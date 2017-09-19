<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;

	use shanemcc\socketrelayserver\iface\ReportHandler;
	use shanemcc\socketrelayserver\impl\SocketRelay\ServerSocketHandler;

	abstract class TargettedMessage extends MessageHandler {

		/** {@inheritdoc} */
		public function handleMessage(ServerSocketHandler $handler, String $number, String $key, String $messageParams): bool {
			$messageType = $this->getMessageType();
			$bits = explode(' ', $messageParams, 2);
			$target = $bits[0];
			$message = isset($bits[1]) ? $bits[1] : '';

			if (!empty($target) && !empty($message) && $handler->isValidTarget($key, $messageType, $target)) {
				$reportHandler = $handler->getServer()->getReportHandler();

				if ($reportHandler instanceof ReportHandler) {
					$reportHandler->handle($handler, $messageType, $number, $key, $messageParams);
					return true;
				}
			}

			return false;
		}

	}
