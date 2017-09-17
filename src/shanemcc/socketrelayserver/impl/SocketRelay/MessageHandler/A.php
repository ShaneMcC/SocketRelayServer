<?php

	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;
	use shanemcc\socketrelayserver\iface\ReportHandler;

	class A extends MessageHandler {
		/** @inheritDoc. */
		public function getMessageType(): String {
			return 'A';
		}

		/** @inheritDoc. */
		public function getDescription(): String {
			return 'List known message types';
		}

		/** @inheritDoc */
		public function handleMessage(String $number, String $key, String $messageParams): bool {
			$messageBits = explode(' ', $messageParams);

			$messageBits[0] = strtoupper($messageBits[0]);

			if ($messageBits[0] == 'RAW') {
				$reportHandler = $this->getSocketHandler()->getServer()->getReportHandler();

				if ($reportHandler instanceof ReportHandler) {
					$reportHandler->handle($this->getSocketHandler(), 'A', $number, $key, implode(' ', $messageBits));
					return true;
				}
			} else if ($messageBits[0] == 'KILL') {
				$reason = isset($messageBits[1]) ? $messageBits[1] : 'Server closing.';
				$this->getSocketHandler()->getServer()->getSocketServer()->close($reason);
				return true;
			}

			return false;
		}

	}
