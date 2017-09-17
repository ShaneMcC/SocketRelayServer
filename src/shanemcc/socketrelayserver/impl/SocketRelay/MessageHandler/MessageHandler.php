<?php

	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;
	use shanemcc\socketrelayserver\impl\SocketRelay\SocketHandler;

	abstract class MessageHandler {
		/** @var SocketHandler Our socker handler. */
		private $handler;

		/**
		 * Create a new Message Handler.
		 *
		 * @param SocketHandler $handler Our socket handler.
		 */
		public function __construct(SocketHandler $handler) {
			$this->handler = $handler;
		}

		/**
		 * Get our socket handler.
		 *
		 * @return SockerHandler Our socket handler.
		 */
		public function getSocketHandler(): SocketHandler {
			return $this->handler;
		}

		/**
		 * Get the MessageType of this handler.
		 *
		 * @return String MessageType of handler.
		 */
		public abstract function getMessageType(): String;

		/**
		 * Get the description of this handler.
		 *
		 * @return String description of handler.
		 */
		public abstract function getDescription(): String;

		/**
		 * Handle this message.
		 *
		 * @param String $number 'Number' from client
		 * @param String $key Key that was given.
		 * @param String $messageParams Params that were given
		 * @return bool True if message was handled or false if we should fire
		 *              the invalid message handler.
		 */
		public abstract function handleMessage(String $number, String $key, String $messageParams): bool;

	}
