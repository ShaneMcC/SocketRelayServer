<?php
	namespace shanemcc\socketrelay\iface;

	use shanemcc\socket\iface\MessageLoop;

	/**
	 * Deal with sending a report somewhere.
	 */
	interface ReportHandlerSetup {
		/**
		 * Called to setup a ReportHandler.
		 *
		 * @param MessageLoop $loop Message Loop
		 * @param Array $clientConf Client Config.
		 * @return ReportHandler that we have setup
		 */
		public function setup(MessageLoop $loop, Array $clientConf): ReportHandler;

		/**
		 * Called to update a ReportHandler.
		 *
		 * @param ReportHandler $reportHandler Active handler.
		 * @param Array $oldConfig Old Client Config.
		 * @param Array $newConfig New Client Config.
		 */
		public function update(ReportHandler $reportHandler, Array $oldConfig, Array $newConfig);
	}
