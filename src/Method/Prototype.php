<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server\Method;

use function array_key_exists;
use function count;
use function is_array;
use function is_numeric;
use function is_string;
use function method_exists;
use function ucfirst;
/**
 * Method prototype metadata
 *
 * @final This class should not be extended
 */
class Prototype
{
    /** @var string Return type */
    protected $return_type = 'void';
    /** @var array Map parameter names to parameter index */
    protected $parameter_name_map = [];
    /** @var array Method parameters */
    protected $parameters = [];
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
     * Set return value
     *
     * @param  string $returnType
     */
    public function set_return_type($return_type): static
    {
        $this->return_type = $return_type;
        return $this;
    }
    /**
     * Retrieve return type
     *
     * @return string
     */
    public function get_return_type()
    {
        return $this->return_type;
    }
    /**
     * Add a parameter
     *
     * @param  string|Parameter $parameter
     */
    public function add_parameter($parameter): static
    {
        if ($parameter instanceof Parameter) {
            $this->parameters[] = $parameter;
            $name = $parameter->get_name();
            $this->parameter_name_map[$name] = count($this->parameters) - 1;
        } else {
            $parameter = new Parameter(['type' => $parameter]);
            $this->parameters[] = $parameter;
        }
        return $this;
    }
    /**
     * Add parameters
     */
    public function add_parameters(array $parameters): static
    {
        foreach ($parameters as $parameter) {
            $this->add_parameter($parameter);
        }
        return $this;
    }
    /**
     * Set parameters
     */
    public function set_parameters(array $parameters): static
    {
        $this->parameters = [];
        $this->parameter_name_map = [];
        $this->add_parameters($parameters);
        return $this;
    }
    /**
     * Retrieve parameters as list of types
     */
    public function get_parameters(): array
    {
        $types = [];
        foreach ($this->parameters as $parameter) {
            $types[] = $parameter->get_type();
        }
        return $types;
    }
    /**
     * Get parameter objects
     *
     * @return array
     */
    public function get_parameter_objects()
    {
        return $this->parameters;
    }
    /**
     * Retrieve a single parameter by name or index
     *
     * @param  string|int $index
     * @return null|Parameter
     */
    public function get_parameter($index)
    {
        if (!is_string($index) && !is_numeric($index)) {
            return null;
        }
        if (array_key_exists($index, $this->parameter_name_map)) {
            $index = $this->parameter_name_map[$index];
        }
        if (array_key_exists($index, $this->parameters)) {
            return $this->parameters[$index];
        }
        return null;
    }
    /**
     * Set object state from array
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
     * Serialize to array
     */
    public function to_array(): array
    {
        return ['returnType' => $this->get_return_type(), 'parameters' => $this->get_parameters()];
    }
}