<?php

declare(strict_types=1);

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
    protected $returnType = 'void';

    /** @var array Map parameter names to parameter index */
    protected $parameterNameMap = [];

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
            $this->setOptions($options);
        }
    }

    /**
     * Set return value
     *
     * @param  string $returnType
     */
    public function setReturnType($returnType): static
    {
        $this->returnType = $returnType;
        return $this;
    }

    /**
     * Retrieve return type
     *
     * @return string
     */
    public function getReturnType()
    {
        return $this->returnType;
    }

    /**
     * Add a parameter
     *
     * @param  string|Parameter $parameter
     */
    public function addParameter($parameter): static
    {
        if ($parameter instanceof Parameter) {
            $this->parameters[]            = $parameter;
            $name                          = $parameter->getName();
            $this->parameterNameMap[$name] = count($this->parameters) - 1;
        } else {
            $parameter          = new Parameter([
                'type' => $parameter,
            ]);
            $this->parameters[] = $parameter;
        }
        return $this;
    }

    /**
     * Add parameters
     */
    public function addParameters(array $parameters): static
    {
        foreach ($parameters as $parameter) {
            $this->addParameter($parameter);
        }
        return $this;
    }

    /**
     * Set parameters
     */
    public function setParameters(array $parameters): static
    {
        $this->parameters       = [];
        $this->parameterNameMap = [];
        $this->addParameters($parameters);
        return $this;
    }

    /**
     * Retrieve parameters as list of types
     */
    public function getParameters(): array
    {
        $types = [];
        foreach ($this->parameters as $parameter) {
            $types[] = $parameter->getType();
        }
        return $types;
    }

    /**
     * Get parameter objects
     *
     * @return array
     */
    public function getParameterObjects()
    {
        return $this->parameters;
    }

    /**
     * Retrieve a single parameter by name or index
     *
     * @param  string|int $index
     * @return null|Parameter
     */
    public function getParameter($index)
    {
        if (! is_string($index) && ! is_numeric($index)) {
            return null;
        }
        if (array_key_exists($index, $this->parameterNameMap)) {
            $index = $this->parameterNameMap[$index];
        }
        if (array_key_exists($index, $this->parameters)) {
            return $this->parameters[$index];
        }
        return null;
    }

    /**
     * Set object state from array
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
     * Serialize to array
     */
    public function toArray(): array
    {
        return [
            'returnType' => $this->getReturnType(),
            'parameters' => $this->getParameters(),
        ];
    }
}
