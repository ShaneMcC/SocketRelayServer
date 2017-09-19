<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;

	use shanemcc\socketrelayserver\impl\SocketRelay\ServerSocketHandler;

	class HELP extends MessageHandler {
		/** @inheritDoc. */
		public function getMessageType(): String {
			return '??';
		}

		/** @inheritDoc. */
		public function getDescription(): String {
			return 'Show help';
		}

		/** @inheritDoc */
		public function handleMessage(ServerSocketHandler $handler, String $number, String $key, String $messageParams): bool {

			$rType = ($key == '--') ? '--' : '??';

			$handler->sendResponse($number, $rType, '--------------------');
			$handler->sendResponse($number, $rType, 'SocketRelay by Dataforce');
			$handler->sendResponse($number, $rType, '--------------------');
			$handler->sendResponse($number, $rType, 'This service is setup to allow for special commands to be issued over this socket connection');
			$handler->sendResponse($number, $rType, 'The commands follow a special syntax:');
			$handler->sendResponse($number, $rType, '<ID> <KEY> <COMMAND> [Params]');
			$handler->sendResponse($number, $rType, '');
			$handler->sendResponse($number, $rType, '<ID>       The message ID, this can be anything, and is used when replying to commands');
			$handler->sendResponse($number, $rType, '           to enable responses to be matched to queries');
			$handler->sendResponse($number, $rType, '<KEY>      In order to send a command, you must first have a KEY. This is to prevent');
			$handler->sendResponse($number, $rType, '           abuse of the service, and to control which commands are usable, and when.');
			$handler->sendResponse($number, $rType, '<COMMAND>  The command to send. Commands which you have access to will be listed when');
			$handler->sendResponse($number, $rType, '           the \'LS\' command is issued. Commands are case sensitive.');
			$handler->sendResponse($number, $rType, '[Params]   Params are optional, and may or may not be needed by a specific command.');
			$handler->sendResponse($number, $rType, '');
			$handler->sendResponse($number, $rType, 'An example LS command would be:');
			$handler->sendResponse($number, $rType, '00 AAS8D3D LS');
			$handler->sendResponse($number, $rType, '');
			$handler->sendResponse($number, $rType, '----------');
			$handler->sendResponse($number, $rType, '');
			$handler->sendResponse($number, $rType, 'Responses to command also follow a special syntax:');
			$handler->sendResponse($number, $rType, '[<ID> <CODE>] <REPLY>');
			$handler->sendResponse($number, $rType, '');
			$handler->sendResponse($number, $rType, '<ID>       This the same as the ID given when issuing the command. This may also be \'--\'');
			$handler->sendResponse($number, $rType, '           for unrequested responses, or special responses such as this.');
			$handler->sendResponse($number, $rType, '<CODE>     This is a special code related to the response, such as \'ERR\' for an error.');
			$handler->sendResponse($number, $rType, '           Different commands use different response codes.');
			$handler->sendResponse($number, $rType, '<REPLY>    This is the result of the command. It is a freeform text response.');
			$handler->sendResponse($number, $rType, '           Different commands may or may not have further syntax in their responses.');
			$handler->sendResponse($number, $rType, '');
			$handler->sendResponse($number, $rType, 'An example LS response to the above command would be:');
			$handler->sendResponse($number, $rType, '[00 LS] # Name -- Desc');
			$handler->sendResponse($number, $rType, '[00 LS] Q -- Close the connection');
			$handler->sendResponse($number, $rType, '[00 LS] LS -- List known message types');
			$handler->sendResponse($number, $rType, '');
			$handler->sendResponse($number, $rType, 'A response with a key and a code of \'--\' is a general notice, or special message.');
			$handler->sendResponse($number, $rType, '');
			$handler->sendResponse($number, $rType, '----------');
			$handler->sendResponse($number, $rType, '');
			$handler->sendResponse($number, $rType, 'The service also accepts "Oblong"-style messages, in the format: <KEY> <CHANNEL> <MESSAGE>');
			$handler->sendResponse($number, $rType, 'These are interpreted as: \'-- <KEY> CM <CHANNEL> <MESSAGE>\' and then processed as normal.');
			$handler->sendResponse($number, $rType, '');
			$handler->sendResponse($number, $rType, '----------');
			$handler->sendResponse($number, $rType, '');
			$handler->sendResponse($number, $rType, 'The socket will stay open until:');
			$handler->sendResponse($number, $rType, '    1) It is closed by the client');
			$handler->sendResponse($number, $rType, '    2) the \'Q\' command is used');
			$handler->sendResponse($number, $rType, '    3) the <ID> \'--\' is used');
			$handler->sendResponse($number, $rType, '    4) The connection is left idle for too long');
			$handler->sendResponse($number, $rType, '    5) An invalid key is used');
			$handler->sendResponse($number, $rType, '');
			$handler->sendResponse($number, $rType, '--------------------');
			$handler->sendResponse($number, $rType, 'If you do not have a key, then you will not be able to use this service.');
			$handler->sendResponse($number, $rType, '--------------------');

			return true;
		}

	}
