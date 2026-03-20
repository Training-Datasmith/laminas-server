<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server\Reflection;

use function array_merge;
use function array_shift;
use function array_unshift;
use function call_user_func_array;
use function count;
use Deprecated;
use function get_object_vars;
use function is_array;
use function is_string;
use Laminas\Code\Reflection\Doc_Block\Tag\Param_Tag;
use Laminas\Code\Reflection\Doc_Block\Tag\Return_Tag;
use Laminas\Code\Reflection\Doc_Block_Reflection;
use function method_exists;
use function preg_match;
use function property_exists;
use ReflectionClass as PhpReflectionClass;
use ReflectionFunction as PhpReflectionFunction;
use ReflectionMethod as PhpReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter as PhpReflectionParameter;
/**
 * Function/Method Reflection
 *
 * Decorates a ReflectionFunction. Allows setting and retrieving an alternate
 * 'service' name (i.e., the name to be used when calling via a service),
 * setting and retrieving the description (originally set using the docblock
 * contents), retrieving the callback and callback type, retrieving additional
 * method invocation arguments, and retrieving the
 * method {@link \Laminas\Server\Reflection\Prototype prototypes}.
 */
abstract class Abstract_Function
{
    /**
     * Additional arguments to pass to method on invocation
     *
     * @var array
     */
    protected $argv = [];
    /**
     * Used to store extra configuration for the method (typically done by the
     * server class, e.g., to indicate whether or not to instantiate a class).
     * Associative array; access is as properties via {@link __get()} and
     * {@link __set()}
     *
     * @var array
     */
    protected $config = [];
    /**
     * Declaring class (needed for when serialization occurs)
     */
    protected string $class;
    /**
     * Function name (needed for serialization)
     */
    protected string $name;
    /**
     * Function/method description
     *
     * @var string
     */
    protected $description = '';
    /**
     * Namespace with which to prefix function/method name
     *
     * @var string
     */
    protected $namespace;
    /**
     * Prototypes
     *
     * @var array
     */
    protected $prototypes = [];
    /**
     * Phpdoc comment
     *
     * @var string
     */
    protected $doc_comment = '';
    /** @var array */
    protected $return = [];
    /** @var string */
    protected $return_desc;
    /** @var array<int, string> */
    protected $param_desc;
    /** @var array */
    protected $sig_params;
    /** @var int */
    protected $sig_params_depth;
    /**
     * Constructor
     *
     * @param null|string $namespace
     * @param null|array $argv
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function __construct(protected \Reflection_Function_Abstract $reflection, $namespace = null, $argv = [])
    {
        // Determine namespace
        if (null !== $namespace) {
            $this->set_namespace($namespace);
        }
        // Determine arguments
        if (is_array($argv)) {
            $this->argv = $argv;
        }
        // If method call, need to store some info on the class
        if ($this->reflection instanceof Php_Reflection_Method) {
            $this->class = $this->reflection->get_declaring_class()->get_name();
        }
        $this->name = $this->reflection->get_name();
        // Perform some introspection
        $this->reflect();
    }
    /**
     * Create signature node tree
     *
     * Recursive method to build the signature node tree. Increments through
     * each array in {@link $sigParams}, adding every value of the next level
     * to the current value (unless the current value is null).
     *
     * @param int $level
     * @return void
     */
    protected function add_tree(Node $parent, $level = 0)
    {
        if ($level >= $this->sig_params_depth) {
            return;
        }
        foreach ($this->sig_params[$level] as $value) {
            $node = new Node($value, $parent);
            if (null !== $value && $this->sig_params_depth > $level + 1) {
                $this->add_tree($node, $level + 1);
            }
        }
    }
    /**
     * Build the signature tree
     *
     * Builds a signature tree starting at the return values and descending
     * through each method argument. Returns an array of
     * {@link \Laminas\Server\Reflection\Node}s.
     *
     * @return array
     */
    protected function build_tree()
    {
        $return_tree = [];
        foreach ($this->return as $value) {
            $node = new Node($value);
            $this->add_tree($node);
            $return_tree[] = $node;
        }
        return $return_tree;
    }
    /**
     * Build method signatures
     *
     * Builds method signatures using the array of return types and the array of
     * parameters types
     *
     * @param array $return Array of return types
     * @param string $returnDesc Return value description
     * @param array $paramTypes Array of arguments (each an array of types)
     * @param array<int, string> $paramDesc Array of parameter descriptions
     */
    protected function build_signatures($return, $return_desc, $param_types, $param_desc): void
    {
        $this->return = $return;
        $this->return_desc = $return_desc;
        $this->param_desc = $param_desc;
        $this->sig_params = $param_types;
        $this->sig_params_depth = count($param_types);
        $signature_trees = $this->build_tree();
        $signatures = [];
        $end_points = [];
        foreach ($signature_trees as $root) {
            $tmp = $root->get_end_points();
            if (empty($tmp)) {
                $end_points = array_merge($end_points, [$root]);
            } else {
                $end_points = array_merge($end_points, $tmp);
            }
        }
        foreach ($end_points as $node) {
            if (!$node instanceof Node) {
                continue;
            }
            $signature = [];
            do {
                array_unshift($signature, $node->get_value());
                $node = $node->get_parent();
            } while ($node instanceof Node);
            $signatures[] = $signature;
        }
        // Build prototypes
        $params = $this->reflection->get_parameters();
        foreach ($signatures as $signature) {
            $return = new Reflection_Return_Value(array_shift($signature), $this->return_desc);
            $tmp = [];
            foreach ($signature as $key => $type) {
                $param = new ReflectionParameter($params[$key], $type, $this->param_desc[$key] ?? null);
                $param->set_position($key);
                $tmp[] = $param;
            }
            $this->prototypes[] = new Prototype($return, $tmp);
        }
    }
    /**
     * Use code reflection to create method signatures
     *
     * Determines the method help/description text from the function DocBlock
     * comment. Determines method signatures using a combination of
     * ReflectionFunction and parsing of DocBlock @param and @return values.
     *
     * @throws Exception\RuntimeException
     * @return void
     */
    protected function reflect()
    {
        $function = $this->reflection;
        $param_count = $function->get_number_of_parameters();
        $parameters = $function->get_parameters();
        if (!$this->doc_comment) {
            $this->doc_comment = $function->get_doc_comment();
        }
        $scanner = new Doc_Block_Reflection($this->doc_comment ?: '/***/');
        $help_text = $scanner->get_long_description();
        /** @var ParamTag[] $paramTags */
        $param_tags = $scanner->get_tags('param');
        /** @var ReturnTag $returnTag */
        $return_tag = $scanner->get_tag('return');
        if (empty($help_text)) {
            $help_text = $scanner->get_short_description();
            if (empty($help_text)) {
                $help_text = $function->get_name();
            }
        }
        $this->set_description($help_text);
        if ($return_tag) {
            $return = [];
            $return_desc = $return_tag->get_description();
            foreach ($return_tag->get_types() as $type) {
                $return[] = $type;
            }
        } else {
            $return = ['void'];
            $return_desc = '';
        }
        $param_types_tmp = [];
        $param_desc = [];
        if (empty($param_tags)) {
            foreach ($parameters as $param) {
                // Suppressing, because false positive
                $param_types_tmp[] = [$this->param_is_array($param) ? 'array' : 'mixed'];
                $param_desc[] = '';
            }
        } else {
            $param_desc = [];
            foreach ($param_tags as $param_tag) {
                $param_types_tmp[] = $param_tag->get_types();
                $param_desc[] = $param_tag->get_description() ?: '';
            }
        }
        // Get all param types as arrays
        $n_param_types_tmp = count($param_types_tmp);
        if ($n_param_types_tmp < $param_count) {
            $start = $param_count - $n_param_types_tmp;
            for ($i = $start; $i < $param_count; ++$i) {
                $param_types_tmp[$i] = ['mixed'];
                $param_desc[$i] = '';
            }
        } elseif ($n_param_types_tmp !== $param_count) {
            throw new Exception\RuntimeException('Variable number of arguments is not supported for services (except optional parameters). ' . 'Number of function arguments must correspond to actual number of arguments described in a docblock.');
        }
        $param_types = [];
        foreach ($param_types_tmp as $i => $param) {
            if ($parameters[$i]->is_optional()) {
                array_unshift($param, null);
            }
            $param_types[] = $param;
        }
        $this->build_signatures($return, $return_desc, $param_types, $param_desc);
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
        throw new Exception\BadMethodCallException('Invalid reflection method ("' . $method . '")');
    }
    /**
     * Retrieve configuration parameters
     *
     * Values are retrieved by key from {@link $config}. Returns null if no
     * value found.
     */
    public function __get(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }
    /**
     * Set configuration parameters
     *
     * Values are stored by $key in {@link $config}.
     *
     * @return void
     */
    public function __set(string $key, mixed $value)
    {
        $this->config[$key] = $value;
    }
    /**
     * Set method's namespace
     *
     * @param string $namespace
     * @throws Exception\InvalidArgumentException
     */
    public function set_namespace($namespace): void
    {
        if (empty($namespace)) {
            $this->namespace = '';
            return;
        }
        if (!is_string($namespace) || !preg_match('/[a-z0-9_\.]+/i', $namespace)) {
            throw new Exception\InvalidArgumentException('Invalid namespace');
        }
        $this->namespace = $namespace;
    }
    /**
     * Return method's namespace
     *
     * @return string
     */
    public function get_namespace()
    {
        return $this->namespace;
    }
    /**
     * Set the description
     *
     * @param string $string
     * @throws Exception\InvalidArgumentException
     */
    public function set_description($string): void
    {
        if (!is_string($string)) {
            throw new Exception\InvalidArgumentException('Invalid description');
        }
        $this->description = $string;
    }
    /**
     * Retrieve the description
     *
     * @return string
     */
    public function get_description()
    {
        return $this->description;
    }
    /**
     * Retrieve all prototypes as array of
     * {@link \Laminas\Server\Reflection\Prototype}s
     *
     * @return Prototype[]
     */
    public function get_prototypes()
    {
        return $this->prototypes;
    }
    /**
     * Retrieve additional invocation arguments
     *
     * @return array
     */
    public function get_invoke_arguments()
    {
        return $this->argv;
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
        $data = [];
        foreach (get_object_vars($this) as $name => $value) {
            if ($value instanceof Php_Reflection_Function || $value instanceof Php_Reflection_Method) {
                continue;
                // never serialize reflection objects
            }
            $data[$name] = $value;
        }
        $data['__reflection_kind'] = $this->reflection instanceof Php_Reflection_Method ? 'method' : 'function';
        return $data;
    }
    /**
     * @return void
     */
    #[Deprecated('Use __unserialize instead')]
    public function __wakeup()
    {
        $this->__unserialize($this->__serialize());
    }
    /**
     * Wakeup from serialization
     *
     * Reflection needs explicit instantiation to work correctly. Re-instantiate
     * reflection object on wakeup.
     */
    public function __unserialize(array $data): void
    {
        $kind = $data['__reflection_kind'] ?? 'function';
        unset($data['__reflection_kind']);
        foreach ($data as $name => $value) {
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }
        if ($kind === 'method') {
            $class = new Php_Reflection_Class($this->class);
            $this->reflection = new Php_Reflection_Method($class->new_instance(), $this->name);
        } else {
            $this->reflection = new Php_Reflection_Function($this->name);
        }
    }
    private function param_is_array(Php_Reflection_Parameter $param): bool
    {
        $type = $param->get_type();
        return $type instanceof ReflectionNamedType && $type->get_name() === 'array';
    }
}