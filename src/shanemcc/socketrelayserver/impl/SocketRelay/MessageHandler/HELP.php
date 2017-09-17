<?php

	namespace shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler;

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
		public function handleMessage(String $number, String $key, String $messageParams): bool {

			$rType = ($key == '--') ? '--' : '??';

			$this->getSocketHandler()->sendResponse($number, $rType, '--------------------');
			$this->getSocketHandler()->sendResponse($number, $rType, 'SocketRelay by Dataforce');
			$this->getSocketHandler()->sendResponse($number, $rType, '--------------------');
			$this->getSocketHandler()->sendResponse($number, $rType, 'This service is setup to allow for special commands to be issued over this socket connection');
			$this->getSocketHandler()->sendResponse($number, $rType, 'The commands follow a special syntax:');
			$this->getSocketHandler()->sendResponse($number, $rType, '<ID> <KEY> <COMMAND> [Params]');
			$this->getSocketHandler()->sendResponse($number, $rType, '');
			$this->getSocketHandler()->sendResponse($number, $rType, '<ID>       The message ID, this can be anything, and is used when replying to commands');
			$this->getSocketHandler()->sendResponse($number, $rType, '           to enable responses to be matched to queries');
			$this->getSocketHandler()->sendResponse($number, $rType, '<KEY>      In order to send a command, you must first have a KEY. This is to prevent');
			$this->getSocketHandler()->sendResponse($number, $rType, '           abuse of the service, and to control which commands are usable, and when.');
			$this->getSocketHandler()->sendResponse($number, $rType, '<COMMAND>  The command to send. Commands which you have access to will be listed when');
			$this->getSocketHandler()->sendResponse($number, $rType, '           the \'LS\' command is issued. Commands are case sensitive.');
			$this->getSocketHandler()->sendResponse($number, $rType, '[Params]   Params are optional, and may or may not be needed by a specific command.');
			$this->getSocketHandler()->sendResponse($number, $rType, '');
			$this->getSocketHandler()->sendResponse($number, $rType, 'An example LS command would be:');
			$this->getSocketHandler()->sendResponse($number, $rType, '00 AAS8D3D LS');
			$this->getSocketHandler()->sendResponse($number, $rType, '');
			$this->getSocketHandler()->sendResponse($number, $rType, '----------');
			$this->getSocketHandler()->sendResponse($number, $rType, '');
			$this->getSocketHandler()->sendResponse($number, $rType, 'Responses to command also follow a special syntax:');
			$this->getSocketHandler()->sendResponse($number, $rType, '[<ID> <CODE>] <REPLY>');
			$this->getSocketHandler()->sendResponse($number, $rType, '');
			$this->getSocketHandler()->sendResponse($number, $rType, '<ID>       This the same as the ID given when issuing the command. This may also be \'--\'');
			$this->getSocketHandler()->sendResponse($number, $rType, '           for unrequested responses, or special responses such as this.');
			$this->getSocketHandler()->sendResponse($number, $rType, '<CODE>     This is a special code related to the response, such as \'ERR\' for an error.');
			$this->getSocketHandler()->sendResponse($number, $rType, '           Different commands use different response codes.');
			$this->getSocketHandler()->sendResponse($number, $rType, '<REPLY>    This is the result of the command. It is a freeform text response.');
			$this->getSocketHandler()->sendResponse($number, $rType, '           Different commands may or may not have further syntax in their responses.');
			$this->getSocketHandler()->sendResponse($number, $rType, '');
			$this->getSocketHandler()->sendResponse($number, $rType, 'An example LS response to the above command would be:');
			$this->getSocketHandler()->sendResponse($number, $rType, '[00 LS] # Name -- Desc');
			$this->getSocketHandler()->sendResponse($number, $rType, '[00 LS] Q -- Close the connection');
			$this->getSocketHandler()->sendResponse($number, $rType, '[00 LS] LS -- List known message types');
			$this->getSocketHandler()->sendResponse($number, $rType, '');
			$this->getSocketHandler()->sendResponse($number, $rType, 'A response with a key and a code of \'--\' is a general notice, or special message.');
			$this->getSocketHandler()->sendResponse($number, $rType, '');
			$this->getSocketHandler()->sendResponse($number, $rType, '----------');
			$this->getSocketHandler()->sendResponse($number, $rType, '');
			$this->getSocketHandler()->sendResponse($number, $rType, 'The socket will stay open until:');
			$this->getSocketHandler()->sendResponse($number, $rType, '    1) It is closed by the client');
			$this->getSocketHandler()->sendResponse($number, $rType, '    2) the \'Q\' command is used');
			$this->getSocketHandler()->sendResponse($number, $rType, '    3) the <ID> \'--\' is used');
			$this->getSocketHandler()->sendResponse($number, $rType, '    4) The connection is left idle for too long');
			$this->getSocketHandler()->sendResponse($number, $rType, '    5) An invalid key is used');
			$this->getSocketHandler()->sendResponse($number, $rType, '');
			$this->getSocketHandler()->sendResponse($number, $rType, '--------------------');
			$this->getSocketHandler()->sendResponse($number, $rType, 'If you do not have a key, then you need to contact the bot owner to get one.');
			$this->getSocketHandler()->sendResponse($number, $rType, '--------------------');

			return true;
		}

	}
