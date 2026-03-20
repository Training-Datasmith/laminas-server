<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server;

use function call_user_func_array;
use function is_object;
use Override;
use ReflectionClass;
/**
 * Abstract Server implementation
 */
abstract class Abstract_Server implements Server
{
    /** @var bool Flag; whether or not overwriting existing methods is allowed */
    protected $overwrite_existing_methods = false;
    protected \Laminas\Server\Definition $table;
    /**
     * Constructor
     *
     * Setup server description
     */
    public function __construct()
    {
        $this->table = new Definition();
        $this->table->set_overwrite_existing_methods($this->overwrite_existing_methods);
    }
    /**
     * Returns a list of registered methods
     *
     * Returns an array of method definitions.
     *
     * @return Definition
     */
    #[Override]
    public function get_functions()
    {
        return $this->table;
    }
    /**
     * Build callback for method signature
     *
     * @return Method\Callback
     */
    protected function build_callback(Reflection\Abstract_Function $reflection)
    {
        $callback = new Method\Callback();
        if ($reflection instanceof Reflection\ReflectionMethod) {
            /** @var string $declaringClass */
            $declaring_class = $reflection->get_declaring_class()->get_name();
            /** @var string $methodName */
            $method_name = $reflection->get_name();
            $callback->set_type($reflection->is_static() ? 'static' : 'instance')->set_class($declaring_class)->set_method($method_name);
        } elseif ($reflection instanceof Reflection\ReflectionFunction) {
            /** @var string $functionName */
            $function_name = $reflection->get_name();
            $callback->set_type('function')->set_function($function_name);
        }
        return $callback;
    }
    /**
     * Build callback for method signature
     *
     * @deprecated Since 2.7.0; method will be removed in 3.0, use
     *             buildCallback() instead.
     *
     * @return Method\Callback
     */
    // @codingStandardsIgnoreStart
    protected function _build_callback(Reflection\Abstract_Function $reflection)
    {
        // @codingStandardsIgnoreEnd
        return $this->build_callback($reflection);
    }
    /**
     * Build a method signature
     *
     * @param  null|string|object $class
     * @return Method\Definition
     * @throws Exception\RuntimeException On duplicate entry.
     */
    final protected function build_signature(Reflection\Abstract_Function $reflection, $class = null)
    {
        $ns = $reflection->get_namespace();
        $name = $reflection->get_name();
        $method = empty($ns) ? $name : $ns . '.' . $name;
        if (!$this->overwrite_existing_methods && $this->table->has_method($method)) {
            throw new Exception\RuntimeException('Duplicate method registered: ' . $method);
        }
        $definition = new Method\Definition();
        $definition->set_name($method)->set_callback($this->build_callback($reflection))->set_method_help($reflection->get_description())->set_invoke_arguments($reflection->get_invoke_arguments());
        foreach ($reflection->get_prototypes() as $proto) {
            $prototype = new Method\Prototype();
            $prototype->set_return_type($this->_fix_type($proto->get_return_type()));
            foreach ($proto->get_parameters() as $parameter) {
                $param = new Method\Parameter(['type' => $this->_fix_type($parameter->get_type()), 'name' => $parameter->get_name(), 'optional' => $parameter->is_optional()]);
                if ($parameter->is_default_value_available()) {
                    $param->set_default_value($parameter->get_default_value());
                }
                $prototype->add_parameter($param);
            }
            $definition->add_prototype($prototype);
        }
        if (is_object($class)) {
            $definition->set_object($class);
        }
        $this->table->add_method($definition);
        return $definition;
    }
    /**
     * Build a method signature
     *
     * @deprecated Since 2.7.0; method will be removed in 3.0, use
     *             buildSignature() instead.
     *
     * @param  null|string|object $class
     * @return Method\Definition
     * @throws Exception\RuntimeException on duplicate entry
     */
    // @codingStandardsIgnoreStart
    protected function _build_signature(Reflection\Abstract_Function $reflection, $class = null)
    {
        // @codingStandardsIgnoreEnd
        return $this->build_signature($reflection, $class);
    }
    /**
     * Dispatch method
     *
     * @deprecated Since 2.7.0; method will be renamed to remove underscore
     *     prefix in 3.0.
     *
     * @return mixed
     */
    // @codingStandardsIgnoreStart
    protected function _dispatch(Method\Definition $invokable, array $params)
    {
        // @codingStandardsIgnoreEnd
        $callback = $invokable->get_callback();
        $type = $callback->get_type();
        if ('function' === $type) {
            $function = $callback->get_function();
            return call_user_func_array($function, $params);
        }
        $class = $callback->get_class();
        $method = $callback->get_method();
        if ('static' === $type) {
            return call_user_func_array([$class, $method], $params);
        }
        $object = $invokable->get_object();
        if (!is_object($object)) {
            $invoke_args = $invokable->get_invoke_arguments();
            if (!empty($invoke_args)) {
                $reflection = new ReflectionClass($class);
                $object = $reflection->new_instance_args($invoke_args);
            } else {
                $object = new $class();
            }
        }
        return call_user_func_array([$object, $method], $params);
    }
    // @codingStandardsIgnoreStart
    /**
     * Map PHP type to protocol type
     *
     * @deprecated Since 2.7.0; method will be renamed to remove underscore
     *     prefix in 3.0.
     * @param  string $type
     * @return string
     */
    abstract protected function _fix_type($type);
    // @codingStandardsIgnoreEnd
}