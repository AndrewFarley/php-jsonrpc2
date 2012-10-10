php-jsonrpc2
============

A simple yet robust PHP JSON-RPC 2.0 Client and Server implementation supporting both method and class.method method calls

Tasks to be completed, sort-of in the order of priority

- Write some tutorials/example classes!  URGENT, so people can easily start using this class!
- Make JSON-RPC Server class able to handle based requests not just from stdin.  This feature will allow for us to do stuff like put a JSON-RPC request into a GET parameter so class methods can then handle POSTed (out-of-band) data.  This is to help avoid having to base64 binary data to encapsulate in your JSON-RPC stream when attempting to do things like upload files.
- Github my JSON-RPC 2.0-ish PHP client library (still needs work before I release this into the wild)
- Implement bi-directional JSON-RPC to facilitate a persistent connection from a client to a server (eg: for a flash game)
- Allow the bi-directional server mechanism to work asynchronously and non-blocking by launching threads (or something, if possible) to handle each incoming request on a stream
- Finish and github my JSON-RPC 2.0 web-based consumption/testing code which is a simple visual tool to consume and test your services
