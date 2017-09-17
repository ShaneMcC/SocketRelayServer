<?php

	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;
	use shanemcc\socketrelayserver\iface\ReportHandler;

	abstract class TargettedMessage extends MessageHandler {

		/** @inheritDoc */
		public function handleMessage(String $number, String $key, String $messageParams): bool {
			$messageType = $this->getMessageType();
			$bits = explode(' ', $messageParams, 2);
			$target = $bits[0];
			$message = isset($bits[1]) ? $bits[1] : '';

			if (!empty($target) && !empty($message) && $this->getSocketHandler()->isValidTarget($key, $messageType, $target)) {
				$reportHandler = $this->getSocketHandler()->getServer()->getReportHandler();

				if ($reportHandler instanceof ReportHandler) {
					$reportHandler->handle($this->getSocketHandler(), $messageType, $number, $key, $messageParams);
					return true;
				}
			}

			return false;
		}

	}
