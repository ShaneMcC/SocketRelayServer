<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;

	use shanemcc\socketrelayserver\impl\SocketRelay\ServerSocketHandler;

	abstract class MessageHandler {
		/**
		 * Create a new Message Handler.
		 */
		public function __construct() { }

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
		 * @param SocketHandler $handler SocketHandler that we are handling for.
		 * @param String $number 'Number' from client
		 * @param String $key Key that was given.
		 * @param String $messageParams Params that were given
		 * @return bool True if message was handled or false if we should fire
		 *              the invalid message handler.
		 */
		public abstract function handleMessage(ServerSocketHandler $handler, String $number, String $key, String $messageParams): bool;

	}
