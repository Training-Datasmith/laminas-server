<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server\Reflection;

use function is_string;
/**
 * Return value reflection
 *
 * Stores the return value type and description
 *
 * @final This class should not be extended
 */
class Reflection_Return_Value
{
    /**
     * Return value type
     *
     * @var string
     */
    protected $type;
    /**
     * Return value description
     *
     * @var string
     */
    protected $description;
    /**
     * Constructor
     *
     * @param string $type Return value type
     * @param string $description Return value type
     */
    public function __construct($type = 'mixed', $description = '')
    {
        $this->set_type($type);
        $this->set_description($description);
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
     * Set parameter type
     *
     * @param string|null $type
     * @throws Exception\InvalidArgumentException
     */
    public function set_type($type): void
    {
        if (!is_string($type) && null !== $type) {
            throw new Exception\InvalidArgumentException('Invalid parameter type');
        }
        $this->type = $type;
    }
    /**
     * Retrieve parameter description
     *
     * @return string
     */
    public function get_description()
    {
        return $this->description;
    }
    /**
     * Set parameter description
     *
     * @param string|null $description
     * @throws Exception\InvalidArgumentException
     */
    public function set_description($description): void
    {
        if (!is_string($description) && null !== $description) {
            throw new Exception\InvalidArgumentException('Invalid parameter description');
        }
        $this->description = $description;
    }
}