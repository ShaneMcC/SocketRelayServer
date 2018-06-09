<?php
	namespace shanemcc\irc;

	class IRCConnectionSettings {
		private $nickname = 'IRCClient';
		private $altnickname = 'IRCClient_';
		private $username = '';
		private $realname = 'shanemcc\irc\IRCClient';

		private $host = '127.0.0.1';
		private $port = 6667;
		private $password = '';

		private $autojoin = '';

		public function getNickname(): String {
			return $this->nickname;
		}

		public function setNickname($nickname) {
			$this->nickname = $nickname;

			return $this;
		}

		public function getAltNickname(): String {
			return $this->altnickname;
		}

		public function setAltNickname($altnickname) {
			$this->altnickname = $altnickname;

			return $this;
		}

		public function getUsername(): String {
			return empty($this->username) ? $this->nickname : $this->username;
		}

		public function setUsername(String $username) {
			$this->username = $username;

			return $this;
		}

		public function getRealname(): String {
			return $this->realname;
		}

		public function setRealname(String $realname) {
			$this->realname = $realname;

			return $this;
		}

		public function getHost(): String {
			return $this->host;
		}

		public function setHost(String $host) {
			$this->host = $host;

			return $this;
		}

		public function getPort(): int {
			return $this->port;
		}

		public function setPort(int $port) {
			$this->port = $port;

			return $this;
		}

		public function getPassword(): String {
			return $this->password;
		}

		public function setPassword(String $password) {
			$this->password = $password;

			return $this;
		}

		public function getAutoJoin(): String {
			return $this->autojoin;
		}

		public function setAutoJoin(String $autojoin) {
			$this->autojoin = $autojoin;

			return $this;
		}
	}
