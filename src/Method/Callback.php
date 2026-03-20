<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server\Method;

use function in_array;
use function is_array;
use function is_object;
use Laminas\Server;
use function method_exists;
use function sprintf;
use function ucfirst;
/**
 * Method callback metadata
 *
 * @final This class should not be extended
 */
class Callback
{
    /** @var string Class name for class method callback */
    protected $class;
    /** @var string|callable Function name or callable for function callback */
    protected $function;
    /** @var string Method name for class method callback */
    protected $method;
    /** @var string Callback type */
    protected $type;
    /** @var array Valid callback types */
    protected $types = ['function', 'static', 'instance'];
    /**
     * Constructor
     *
     * @param  null|array $options
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->set_options($options);
        }
    }
    /**
     * Set object state from array of options
     *
     * @return Callback
     */
    public function set_options(array $options): static
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst((string) $key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
        return $this;
    }
    /**
     * Set callback class
     *
     * @param  string $class
     * @return Callback
     */
    public function set_class($class): static
    {
        if (is_object($class)) {
            $class = $class::class;
        }
        $this->class = $class;
        return $this;
    }
    /**
     * Get callback class
     *
     * @return string|null
     */
    public function get_class()
    {
        return $this->class;
    }
    /**
     * Set callback function
     *
     * @param  string|callable $function
     * @return Callback
     */
    public function set_function($function): static
    {
        $this->function = $function;
        $this->set_type('function');
        return $this;
    }
    /**
     * Get callback function
     *
     * @return null|string|callable
     */
    public function get_function()
    {
        return $this->function;
    }
    /**
     * Set callback class method
     *
     * @param  string $method
     * @return Callback
     */
    public function set_method($method): static
    {
        $this->method = $method;
        return $this;
    }
    /**
     * Get callback class  method
     *
     * @return null|string
     */
    public function get_method()
    {
        return $this->method;
    }
    /**
     * Set callback type
     *
     * @param  string $type
     * @return Callback
     * @throws Server\Exception\InvalidArgumentException
     */
    public function set_type($type): static
    {
        if (!in_array($type, $this->types)) {
            throw new Server\Exception\InvalidArgumentException(sprintf('Invalid method callback type "%s" passed to %s', $type, __METHOD__));
        }
        $this->type = $type;
        return $this;
    }
    /**
     * Get callback type
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }
    /**
     * Cast callback to array
     */
    public function to_array(): array
    {
        $type = $this->get_type();
        $array = ['type' => $type];
        if ('function' === $type) {
            $array['function'] = $this->get_function();
        } else {
            $array['class'] = $this->get_class();
            $array['method'] = $this->get_method();
        }
        return $array;
    }
}