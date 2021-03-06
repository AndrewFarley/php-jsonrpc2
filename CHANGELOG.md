v1.4
======
- Added my jsonRPC2Client class which supports...
  - Method chaining and/or dynamic method execution
  - Top-level arguments (for api keys/sessions/etc)
  - Adding HTTP Headers (for api keys/sessions/etc)
  - JSON-RPC 2.0 By-Name (keyed) Parameters (for both method chaining and dynamic method execution)
- When executed via a browser, the sample.php script now also tests our JSON-RPC Client by connecting to the provided SampleJSONRPCServer (effectively looped back)
- The sample.php script now also outputs html instead of plaintext when executed via a browser (although the HTML is quite plain)

v1.3
======
- Moved error codes into static consts
- Removed obsolete type check
- Added error code constants into all applicable exceptions
- Fixed some comments throughout the project

v1.2
======
- Made JSON-RPC server able to handle requests automatically from GET, allowing for further and flexibility and out-of-band requests.
- Fixed a jsonrpc 2.0 by-name parameterization bug when not specifying the names of all parameters

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