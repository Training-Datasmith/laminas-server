<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server\Reflection;

/**
 * Method/Function prototypes
 *
 * Contains accessors for the return value and all method arguments.
 *
 * @final This class should not be extended
 */
class Prototype
{
    /** @var ReflectionParameter[] */
    protected array $params;
    /**
     * @deprecated This property was previously undeclared therefore it requires public access to maintain BC.
     *             It will be declared private in the next major version of this component.
     *
     * @var ReflectionReturnValue
     */
    public $return;
    /**
     * Constructor
     *
     * @param ReflectionParameter[] $params
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(Reflection_Return_Value $return, array $params = [])
    {
        $this->return = $return;
        foreach ($params as $param) {
            if (!$param instanceof ReflectionParameter) {
                throw new Exception\InvalidArgumentException('One or more params are invalid');
            }
        }
        $this->params = $params;
    }
    /**
     * Retrieve return type
     *
     * @return string
     */
    public function get_return_type()
    {
        return $this->return->get_type();
    }
    /**
     * Retrieve the return value object
     *
     * @return ReflectionReturnValue
     */
    public function get_return_value()
    {
        return $this->return;
    }
    /**
     * Retrieve method parameters
     *
     * @return ReflectionParameter[] Array of {@link \Laminas\Server\Reflection\ReflectionParameter}s
     */
    public function get_parameters()
    {
        return $this->params;
    }
}