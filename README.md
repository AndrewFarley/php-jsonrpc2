php-jsonrpc2
============

A simple yet robust PHP JSON-RPC 2.0 Client and Server implementation supporting both method and class.method method calls

Features
============
- Robust and easily extensible JSON-RPC 2 compliant server, drop-in and it's ready to use.
    - Immediately if you have an autoloader or pre-include all your API method classes.
    - Easy to implement your existing session or API key code (with examples)
- Simple as hell JSON-RPC 2 client code, start consuming JSON-RPC services immediately (with local and remote examples)
  - Method chaining and/or dynamic method execution
  - Setting Top-level arguments (for api keys/sessions/etc)
  - Adding HTTP Headers (for api keys/sessions/etc)
  - Supports JSON-RPC 2.0 By-Name (keyed) Parameters in both chained and dynamic method execution (with examples)

Tasks to be completed
============
- Write some/better tutorials/example classes!  URGENT, so people can easily start using this class!
- Implement bi-directional JSON-RPC to facilitate a persistent connection from a client to a server (eg: for a flash game or chat client)
- Allow the bi-directional server mechanism to work asynchronously and non-blocking by launching threads (or something, if possible) to handle each incoming request on a stream
- Finish and github my JSON-RPC 2.0 web-based consumption/testing code which is a simple visual tool to consume and test your services

To get started
==============
- Run the sample.php script (cli or via a webserver) and see how it works, and what it does.  It will walk you through all the things that the library handles
- Then try the (coming soon) client.php and server.php scripts by pointing your web browser to http://yoursite/php-jsonrpc2/client.php
- Then go create your own JSON-RPC 2.0 server, and let me know.  :)