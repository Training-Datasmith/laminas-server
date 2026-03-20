<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server;

use function array_key_exists;
use function count;
use Countable;
use function current;
use function is_array;
use function is_numeric;
use Iterator;
use function key;
use Laminas\Server\Exception\InvalidArgumentException;
use function next;
use Override;
use function reset;
use Return_Type_Will_Change;
use function sprintf;
/**
 * Server methods metadata
 *
 * @final This class should not be extended
 */
class Definition implements Countable, Iterator
{
    /** @var array Array of \Laminas\Server\Method\Definition objects */
    protected $methods = [];
    /** @var bool Whether or not overwriting existing methods is allowed */
    protected $overwrite_existing_methods = false;
    /**
     * Constructor
     *
     * @param  null|array $methods
     */
    public function __construct($methods = null)
    {
        if (is_array($methods)) {
            $this->set_methods($methods);
        }
    }
    /**
     * Set flag indicating whether or not overwriting existing methods is allowed
     *
     * @param mixed $flag
     */
    public function set_overwrite_existing_methods($flag): static
    {
        $this->overwrite_existing_methods = (bool) $flag;
        return $this;
    }
    /**
     * Add method to definition
     *
     * @param  array|\Laminas\Server\Method\Definition $method
     * @param  null|string $name
     * @throws InvalidArgumentException If duplicate or invalid method provided.
     */
    public function add_method($method, $name = null): static
    {
        if (is_array($method)) {
            $method = new Method\Definition($method);
        } elseif (!$method instanceof Method\Definition) {
            throw new InvalidArgumentException('Invalid method provided');
        }
        if (is_numeric($name)) {
            $name = null;
        }
        if (null !== $name) {
            $method->set_name($name);
        } else {
            $name = $method->get_name();
        }
        if (null === $name) {
            throw new InvalidArgumentException('No method name provided');
        }
        if (!$this->overwrite_existing_methods && array_key_exists($name, $this->methods)) {
            throw new InvalidArgumentException(sprintf('Method by name of "%s" already exists', $name));
        }
        $this->methods[$name] = $method;
        return $this;
    }
    /**
     * Add multiple methods
     *
     * @param  array $methods Array of \Laminas\Server\Method\Definition objects or arrays
     */
    public function add_methods(array $methods): static
    {
        foreach ($methods as $key => $method) {
            $this->add_method($method, $key);
        }
        return $this;
    }
    /**
     * Set all methods at once (overwrite)
     *
     * @param  array $methods Array of \Laminas\Server\Method\Definition objects or arrays
     */
    public function set_methods(array $methods): static
    {
        $this->clear_methods();
        $this->add_methods($methods);
        return $this;
    }
    /**
     * Does the definition have the given method?
     *
     * @param  string $method
     */
    public function has_method($method): bool
    {
        return array_key_exists($method, $this->methods);
    }
    /**
     * Get a given method definition
     *
     * @param  string $method
     * @return null|false|\Laminas\Server\Method\Definition
     */
    public function get_method($method)
    {
        if ($this->has_method($method)) {
            return $this->methods[$method];
        }
        return false;
    }
    /**
     * Get all method definitions
     *
     * @return array Array of \Laminas\Server\Method\Definition objects
     */
    public function get_methods()
    {
        return $this->methods;
    }
    /**
     * Remove a method definition
     *
     * @param  string $method
     */
    public function remove_method($method): static
    {
        if ($this->has_method($method)) {
            unset($this->methods[$method]);
        }
        return $this;
    }
    /**
     * Clear all method definitions
     */
    public function clear_methods(): static
    {
        $this->methods = [];
        return $this;
    }
    /**
     * Cast definition to an array
     */
    public function to_array(): array
    {
        $methods = [];
        foreach ($this->get_methods() as $key => $method) {
            $methods[$key] = $method->to_array();
        }
        return $methods;
    }
    /**
     * Countable: count of methods
     *
     * @return int
     */
    #[Override]
    #[Return_Type_Will_Change]
    public function count()
    {
        return count($this->methods);
    }
    /**
     * Iterator: current item
     *
     * @return Method\Definition
     */
    #[Override]
    #[Return_Type_Will_Change]
    public function current()
    {
        return current($this->methods);
    }
    /**
     * Iterator: current item key
     *
     * @return int|string
     */
    #[Override]
    #[Return_Type_Will_Change]
    public function key()
    {
        return key($this->methods);
    }
    /**
     * Iterator: advance to next method
     *
     * @return Method\Definition
     */
    #[Override]
    #[Return_Type_Will_Change]
    public function next()
    {
        return next($this->methods);
    }
    /**
     * Iterator: return to first method
     */
    #[Override]
    #[Return_Type_Will_Change]
    public function rewind(): void
    {
        reset($this->methods);
    }
    /**
     * Iterator: is the current index valid?
     *
     * @return bool
     */
    #[Override]
    #[Return_Type_Will_Change]
    public function valid()
    {
        return (bool) $this->current();
    }
}