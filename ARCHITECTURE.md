# Architecture: laminas-server

## Purpose
An abstract server framework that provides the infrastructure for implementing RPC-style servers (XML-RPC, JSON-RPC, SOAP). Handles service method reflection, method definition building, and request dispatching.

## Directory Structure
```
src/
  Server_Interface.php          # Contract: addFunction, setClass, handle, getFunctions
  Abstract_Server.php           # Base implementation — method registration, dispatch logic
  Server.php                    # Concrete implementation
  Definition.php                # Collection of method definitions for a server
  Cache.php                     # Serializes/deserializes server definition to a cache file
  Client.php / Client_Interface.php  # Generic RPC client
  Reflection.php                # Facade for reflecting functions and classes
  Reflection/
    Abstract_Function.php       # Shared reflection for functions and methods
    Reflection_Class.php        # Reflects a class and its public methods
    Reflection_Function.php     # Reflects a standalone function
    Reflection_Method.php       # Reflects a class method
    Reflection_Parameter.php    # Reflects a parameter (type from PHPDoc or type hint)
    Reflection_Return_Value.php # Reflects a return value type (from PHPDoc)
    Prototype.php               # A specific calling signature (one @param combination)
    Node.php                    # PHPDoc parse tree node
  Method/
    Callback.php                # Maps a method name to a callable
    Definition.php              # Describes a single RPC method (name, params, return type)
    Parameter.php               # A parameter descriptor (name, type, optional, default)
    Prototype.php               # A specific parameter combination for overloaded methods
  Exception/                    # Typed exceptions
```

## Key Design Decisions
- **PHPDoc-driven typing** — parameter and return types are extracted from PHPDoc `@param` and `@return` annotations, not just PHP type hints. This powers protocol-specific type serialization (e.g., XML-RPC integer vs. string).
- **Prototype overloading** — a method can have multiple prototypes (different parameter type combinations), matching the XML-RPC convention of overloaded methods.
- **Definition cache** — the server definition can be serialized and cached via `Cache`, avoiding repeat reflection on warm requests.

## Extension Points
- Extend `Abstract_Server` to implement a concrete RPC protocol (XML-RPC, JSON-RPC).
- Add service methods at runtime via `addFunction()` or `setClass()`.

## Dependency Flow
```
Server::setClass(MyService::class)
  └─ Reflection::reflectClass(MyService::class)
       └─ Reflection_Class → Reflection_Method[] → Prototype[]
            └─ Parameter[] (types from PHPDoc + type hints)
  └─ Definition → Method\Definition[]

Server::handle($request)
  └─ dispatch method name → callable → response
```
