<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server\Reflection;

use function call_user_func_array;
use Deprecated;
use function is_string;
use function method_exists;
use Reflection_Exception;
/**
 * Parameter Reflection
 *
 * Decorates a ReflectionParameter to allow setting the parameter type
 *
 * @final This class should not be extended
 */
class ReflectionParameter
{
    /**
     * Parameter position
     *
     * @var int
     */
    protected $position;
    /**
     * Parameter type
     *
     * @var string
     */
    protected $type;
    /**
     * Parameter description
     *
     * @var string
     */
    protected $description;
    /**
     * Parameter name (needed for serialization)
     */
    protected string $name;
    /**
     * Declaring function name (needed for serialization)
     *
     * @var string
     */
    protected $function_name;
    /**
     * Constructor
     *
     * @param string $type Parameter type
     * @param string $description Parameter description
     */
    public function __construct(protected \ReflectionParameter $reflection, $type = 'mixed', $description = '')
    {
        // Store parameters needed for (un)serialization
        $this->name = $this->reflection->get_name();
        $this->function_name = $this->reflection->get_declaring_class() ? [$this->reflection->get_declaring_class()->get_name(), $this->reflection->get_declaring_function()->get_name()] : $this->reflection->get_declaring_function()->get_name();
        $this->set_type($type);
        $this->set_description($description);
    }
    /**
     * Proxy reflection calls
     *
     * @param array $args
     * @throws Exception\BadMethodCallException
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        if (method_exists($this->reflection, $method)) {
            return call_user_func_array([$this->reflection, $method], $args);
        }
        throw new Exception\BadMethodCallException('Invalid reflection method');
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
    /**
     * Set parameter position
     *
     * @param int $index
     */
    public function set_position($index): void
    {
        $this->position = $index;
    }
    /**
     * Return parameter position
     *
     * @return int
     */
    public function get_position()
    {
        return $this->position;
    }
    /**
     * @return string[]
     */
    #[Deprecated('Use __serialize instead')]
    public function __sleep()
    {
        return $this->__serialize();
    }
    /**
     * @return string[]
     */
    public function __serialize(): array
    {
        return ['position' => $this->position, 'type' => $this->type, 'description' => $this->description, 'name' => $this->name, 'functionName' => $this->function_name];
    }
    /**
     * @return void
     * @throws ReflectionException
     */
    #[Deprecated('Use __unserialize instead')]
    public function __wakeup()
    {
        $this->__unserialize($this->__serialize());
    }
    /**
     * @param array<string, mixed> $data
     * @throws ReflectionException
     */
    public function __unserialize(array $data): void
    {
        $this->position = $data['position'] ?? '0';
        $this->type = $data['type'] ?? 'mixed';
        $this->description = $data['description'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->function_name = $data['functionName'] ?? '';
        $this->reflection = new \ReflectionParameter($this->function_name, $this->name);
    }
}