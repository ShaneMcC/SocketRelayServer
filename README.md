# SocketRelayServer

PHP Implementation of my SocketRelay notification protocol which is designed for
sending messages to IRC.

This service can be configured to either Relay messages to an another
SocketRelay server implementation, or report directly to IRC.

As well as accepting messages to send to IRC or relay to another end point, If
a message fails to relay, it will be queued up and attempted again later, we
also persist these failures to disk to try again later.

The code in this repo is available as-is under the MIT License with no support
offered and primarily exists purely for my own benefit.

The closest thing to any kind of documentation on the protocol is in the [HELP Handler Code](https://github.com/ShaneMcC/SocketRelayServer/blob/master/src/shanemcc/socketrelay/impl/messagehandler/HELP.php).

Configuration can be done using either a `config.local.php` file or ENV vars.
Check [`config.php`](https://github.com/ShaneMcC/SocketRelayServer/blob/master/config.php) for valid config settings.
