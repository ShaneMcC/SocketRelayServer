<?php
	namespace shanemcc\socketrelay\impl\messagehandler;

	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socketrelay\impl\ServerSocketHandler;

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
