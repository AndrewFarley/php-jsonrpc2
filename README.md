php-jsonrpc2
============

A simple yet robust PHP JSON-RPC 2.0 Client and Server implementation supporting both method and class.method method calls

Tasks to be completed, sort-of in the order of priority

TODO: Make JSON-RPC Server class able to handle GET-based requests, so class methods can then handle POSTed (out-of-band) data.  This is to help avoid having to base64 binary data to encapsulate in your JSON-RPC stream when attempting to do things like upload files.
TODO: Github my JSON-RPC 2.0-ish PHP client library (still needs work before I release this into the wild)
TODO: Implement bi-directional JSON-RPC to facilitate a persistent connection from a client to a server (eg: for a flash game)
    SUB-TODO: Allow this persistence mechanism to work asynchronously and non-blocking.  Launching threads (or something, if possible) to handle each incoming request on a stream
TODO: Finish and github my JSON-RPC 2.0 web-based consumption/testing code which is a simple visual tool to consume and test your services
