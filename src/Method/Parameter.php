<?php

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
    protected $defaultValue;

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
            $this->setOptions($options);
        }
    }

    /**
     * Set object state from array of options
     */
    public function setOptions(array $options): static
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst((string) $key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * Set default value
     *
     * @param  mixed $defaultValue
     */
    public function setDefaultValue($defaultValue): static
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * Retrieve default value
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set description
     *
     * @param  mixed $description
     */
    public function setDescription($description): static
    {
        $this->description = (string) $description;
        return $this;
    }

    /**
     * Retrieve description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set name
     *
     * @param  mixed $name
     */
    public function setName($name): static
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * Retrieve name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set optional flag
     *
     * @param  mixed $flag
     */
    public function setOptional($flag): static
    {
        $this->optional = (bool) $flag;
        return $this;
    }

    /**
     * Is the parameter optional?
     *
     * @return bool
     */
    public function isOptional()
    {
        return $this->optional;
    }

    /**
     * Set parameter type
     *
     * @param  mixed $type
     */
    public function setType($type): static
    {
        $this->type = (string) $type;
        return $this;
    }

    /**
     * Retrieve parameter type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Cast to array
     */
    public function toArray(): array
    {
        return [
            'type'         => $this->getType(),
            'name'         => $this->getName(),
            'optional'     => $this->isOptional(),
            'defaultValue' => $this->getDefaultValue(),
            'description'  => $this->getDescription(),
        ];
    }
}
