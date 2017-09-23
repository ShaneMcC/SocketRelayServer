<?php
	namespace shanemcc\irc\OutputQueue;

	abstract class QueuePriority {
		const Low = 0;
		const Normal = 10;
		const High = 20;

		const Immediate = 100;
	}
