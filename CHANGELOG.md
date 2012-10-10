v1.1
======

- Make JSON-RPC Server able to handle requests not just from stdin, allowing for testing and flexibility.  Most likely use this feature in the streaming functionality.  This feature will allow for us to do stuff like put a JSON-RPC request into a GET parameter so class methods can then handle POSTed (out-of-band) data.  This is to help avoid having to base64 binary data to encapsulate in your JSON-RPC stream when attempting to do things like upload files.
- Wrote a simple class (SampleJSONRPCServer) adding debugging to every observer method, a child of the jsonRPC2Server class which has a TON of comments explaining what everything is for with some code examples.  You should probably use this as a skeleton for your own PHP-based JSON-RPC Server
- Wrote a few quick classes and a sample script to run to show off what the class can do.  Will convert this to coverage tests in the near future
- Made class usable on the cli, for testing (and other stuff maybe)
- Added support for detecting classes with __call and __callStatic methods and allowing them to work

v1.0
======

- Initial commit of JSONRPC 2.0 Server class, with no tutorials, helpers, examples, or useful readme file.  More to come...