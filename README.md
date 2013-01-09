php-jsonrpc2
============

A simple yet robust PHP JSON-RPC 2.0 Client and Server implementation supporting both method and class.method method calls

Tasks to be completed, sort-of in the order of priority

- Write some/better tutorials/example classes!  URGENT, so people can easily start using this class!
- Github my JSON-RPC 2.0-ish PHP client library (still needs work before I release this into the wild)
- Implement bi-directional JSON-RPC to facilitate a persistent connection from a client to a server (eg: for a flash game)
- Allow the bi-directional server mechanism to work asynchronously and non-blocking by launching threads (or something, if possible) to handle each incoming request on a stream
- Finish and github my JSON-RPC 2.0 web-based consumption/testing code which is a simple visual tool to consume and test your services

To get started
==============

- Run the sample.php script (cli or via a webserver) and see how it works, and what it does.  It will walk you through all the things that the library handles
- Then try the (coming soon) client.php and server.php scripts by pointing your web browser to http://yoursite/php-jsonrpc2/client.php
- Then go create your own JSON-RPC 2.0 server, and let me know.  :)