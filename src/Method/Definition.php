<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server\Method;

use function is_array;
use function is_object;
use Laminas\Server;
use function method_exists;
use function sprintf;
use function ucfirst;
/**
 * Method definition metadata
 *
 * @final This class should not be extended
 */
class Definition
{
    /** @var Callback */
    protected $callback;
    /** @var array */
    protected $invoke_arguments = [];
    /** @var string */
    protected $method_help = '';
    /** @var string */
    protected $name;
    /** @var null|object */
    protected $object;
    /** @var array Array of \Laminas\Server\Method\Prototype objects */
    protected $prototypes = [];
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
     * Set object state from options
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
     * Set method name
     *
     * @param  string $name
     */
    public function set_name($name): static
    {
        $this->name = $name;
        return $this;
    }
    /**
     * Get method name
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }
    /**
     * Set method callback
     *
     * @param array|Callback $callback
     * @throws Server\Exception\InvalidArgumentException
     */
    public function set_callback($callback): static
    {
        if (is_array($callback)) {
            $callback = new Callback($callback);
        } elseif (!$callback instanceof Callback) {
            throw new Server\Exception\InvalidArgumentException('Invalid method callback provided');
        }
        $this->callback = $callback;
        return $this;
    }
    /**
     * Get method callback
     *
     * @return Callback
     */
    public function get_callback()
    {
        return $this->callback;
    }
    /**
     * Add prototype to method definition
     *
     * @param array|Prototype $prototype
     * @throws Server\Exception\InvalidArgumentException
     */
    public function add_prototype($prototype): static
    {
        if (is_array($prototype)) {
            $prototype = new Prototype($prototype);
        } elseif (!$prototype instanceof Prototype) {
            throw new Server\Exception\InvalidArgumentException('Invalid method prototype provided');
        }
        $this->prototypes[] = $prototype;
        return $this;
    }
    /**
     * Add multiple prototypes at once
     *
     * @param  array $prototypes Array of \Laminas\Server\Method\Prototype objects or arrays
     */
    public function add_prototypes(array $prototypes): static
    {
        foreach ($prototypes as $prototype) {
            $this->add_prototype($prototype);
        }
        return $this;
    }
    /**
     * Set all prototypes at once (overwrites)
     *
     * @param  array $prototypes Array of \Laminas\Server\Method\Prototype objects or arrays
     */
    public function set_prototypes(array $prototypes): static
    {
        $this->prototypes = [];
        $this->add_prototypes($prototypes);
        return $this;
    }
    /**
     * Get all prototypes
     *
     * @return array $prototypes Array of \Laminas\Server\Method\Prototype objects or arrays
     */
    public function get_prototypes()
    {
        return $this->prototypes;
    }
    /**
     * Set method help
     *
     * @param  string $methodHelp
     */
    public function set_method_help($method_help): static
    {
        $this->method_help = $method_help;
        return $this;
    }
    /**
     * Get method help
     *
     * @return string
     */
    public function get_method_help()
    {
        return $this->method_help;
    }
    /**
     * Set object to use with method calls
     *
     * @param  object $object
     * @throws Server\Exception\InvalidArgumentException
     */
    public function set_object($object): static
    {
        if (!is_object($object) && null !== $object) {
            throw new Server\Exception\InvalidArgumentException(sprintf('Invalid object passed to %s', __METHOD__));
        }
        $this->object = $object;
        return $this;
    }
    /**
     * Get object to use with method calls
     *
     * @return null|object
     */
    public function get_object()
    {
        return $this->object;
    }
    /**
     * Set invoke arguments
     */
    public function set_invoke_arguments(array $invoke_arguments): static
    {
        $this->invoke_arguments = $invoke_arguments;
        return $this;
    }
    /**
     * Retrieve invoke arguments
     *
     * @return array
     */
    public function get_invoke_arguments()
    {
        return $this->invoke_arguments;
    }
    /**
     * Serialize to array
     */
    public function to_array(): array
    {
        $prototypes = $this->get_prototypes();
        $signatures = [];
        foreach ($prototypes as $prototype) {
            $signatures[] = $prototype->to_array();
        }
        return ['name' => $this->get_name(), 'callback' => $this->get_callback()->to_array(), 'prototypes' => $signatures, 'methodHelp' => $this->get_method_help(), 'invokeArguments' => $this->get_invoke_arguments(), 'object' => $this->get_object()];
    }
}