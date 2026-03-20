<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server\Method;

use function is_array;
use function method_exists;
use function ucfirst;
/**
 * Method parameter metadata
 *
 * @final This class should not be extended
 */
class Parameter
{
    /**
     * Default parameter value
     *
     * @var mixed
     */
    protected $default_value;
    /**
     * Parameter description
     *
     * @var string
     */
    protected $description = '';
    /**
     * Parameter variable name
     *
     * @var string
     */
    protected $name;
    /**
     * Is parameter optional?
     *
     * @var bool
     */
    protected $optional = false;
    /**
     * Parameter type
     *
     * @var string
     */
    protected $type = 'mixed';
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
     * Set default value
     *
     * @param  mixed $defaultValue
     */
    public function set_default_value($default_value): static
    {
        $this->default_value = $default_value;
        return $this;
    }
    /**
     * Retrieve default value
     *
     * @return mixed
     */
    public function get_default_value()
    {
        return $this->default_value;
    }
    /**
     * Set description
     *
     * @param  mixed $description
     */
    public function set_description($description): static
    {
        $this->description = (string) $description;
        return $this;
    }
    /**
     * Retrieve description
     *
     * @return string
     */
    public function get_description()
    {
        return $this->description;
    }
    /**
     * Set name
     *
     * @param  mixed $name
     */
    public function set_name($name): static
    {
        $this->name = (string) $name;
        return $this;
    }
    /**
     * Retrieve name
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }
    /**
     * Set optional flag
     *
     * @param  mixed $flag
     */
    public function set_optional($flag): static
    {
        $this->optional = (bool) $flag;
        return $this;
    }
    /**
     * Is the parameter optional?
     *
     * @return bool
     */
    public function is_optional()
    {
        return $this->optional;
    }
    /**
     * Set parameter type
     *
     * @param  mixed $type
     */
    public function set_type($type): static
    {
        $this->type = (string) $type;
        return $this;
    }
    /**
     * Retrieve parameter type
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }
    /**
     * Cast to array
     */
    public function to_array(): array
    {
        return ['type' => $this->get_type(), 'name' => $this->get_name(), 'optional' => $this->is_optional(), 'defaultValue' => $this->get_default_value(), 'description' => $this->get_description()];
    }
}