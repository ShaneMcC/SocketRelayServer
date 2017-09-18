# SocketRelayServer

PHP Implementation of my SocketRelay notification protocol which is designed for
sending messages to IRC.

Valid messages sent to this service are accepted and attempted to be relayed to
an alternative SocketRelay end point as defined in the config. This may or may
not be another instance of this service.

Ultimately, the relaying needs to reach an endpoint that can actually deliver
the message to IRC. Currently the code for this part is not (yet?) available.

As well as relaying messages to another end point, If a message fails to relay,
it will be queued up and attempted again later, we also persist these failures
to disk to try again later.

The code in this repo is available as-is under the MIT License with no support
offered and primarily exists purely for my own benefit.

The closest thing to any kind of documentation on the protocol is in the [HELP Handler Code](https://github.com/ShaneMcC/SocketRelayServer/blob/master/src/shanemcc/socketrelayserver/impl/SocketRelay/MessageHandler/HELP.php).
