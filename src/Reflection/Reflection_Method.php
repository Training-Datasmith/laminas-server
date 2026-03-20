<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server\Reflection;

use function array_map;
use function array_merge;
use function implode;
use Override;
use const PHP_EOL;
use function str_contains;
use function str_replace;
/**
 * Method Reflection
 *
 * @final This class should not be extended
 */
class ReflectionMethod extends Abstract_Function
{
    /**
     * Doc block inherit tag for search
     */
    public const INHERIT_TAG = '{@inheritdoc}';
    /**
     * Parent class name
     *
     * @var string
     */
    protected $class;
    /**
     * Constructor
     *
     * @param string $namespace
     * @param array $argv
     */
    public function __construct(
        /**
         * Parent class reflection
         */
        protected \Laminas\Server\Reflection\ReflectionClass $class_reflection,
        \ReflectionMethod $r,
        $namespace = null,
        $argv = []
    )
    {
        $this->reflection = $r;
        $class_namespace = $this->class_reflection->get_namespace();
        // Determine namespace
        if (!empty($namespace)) {
            $this->set_namespace($namespace);
        } elseif (!empty($class_namespace)) {
            $this->set_namespace($class_namespace);
        }
        // Determine arguments
        $this->argv = $argv;
        // If method call, need to store some info on the class
        $this->class = $this->class_reflection->get_name();
        $this->name = $r->get_name();
        // Perform some introspection
        $this->reflect();
    }
    /**
     * Return the reflection for the class that defines this method
     *
     * @return ReflectionClass|\ReflectionClass
     */
    public function get_declaring_class()
    {
        return $this->class_reflection;
    }
    /**
     * Wakeup from serialization
     *
     * Reflection needs explicit instantiation to work correctly. Re-instantiate
     * reflection object on wakeup.
     */
    #[Override]
    public function __unserialize(array $data): void
    {
        $this->class = $data['class'];
        $this->name = $data['name'];
        $this->class_reflection = new ReflectionClass(new \ReflectionClass($this->class), $this->get_namespace(), $this->get_invoke_arguments());
        $this->reflection = new \ReflectionMethod($this->class_reflection->get_name(), $this->name);
    }
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    #[Override]
    protected function reflect()
    {
        $doc_comment = $this->reflection->get_doc_comment();
        if (str_contains($doc_comment, self::INHERIT_TAG)) {
            $this->doc_comment = $this->fetch_recursive_doc_comment();
        }
        parent::reflect();
    }
    /**
     * Fetch all doc comments for inherit values
     */
    private function fetch_recursive_doc_comment(): string
    {
        $current_method_name = $this->reflection->get_name();
        $doc_comment_list[] = $this->reflection->get_doc_comment();
        // fetch all doc blocks for method from parent classes
        $doc_comment_fetched = $this->fetch_recursive_doc_block_from_parent($this->class_reflection, $current_method_name);
        if ($doc_comment_fetched) {
            $doc_comment_list = array_merge($doc_comment_list, $doc_comment_fetched);
        }
        // fetch doc blocks from interfaces
        $interface_reflection_list = $this->class_reflection->get_interfaces();
        foreach ($interface_reflection_list as $interface_reflection) {
            if (!$interface_reflection->has_method($current_method_name)) {
                continue;
            }
            $doc_comment_list[] = $interface_reflection->get_method($current_method_name)->get_doc_comment();
        }
        $normalized_doc_comment_list = array_map(function ($doc_comment): string|array {
            $doc_comment = str_replace('/**', '', $doc_comment);
            return str_replace('*/', '', $doc_comment);
        }, $doc_comment_list);
        return '/**' . implode(PHP_EOL, $normalized_doc_comment_list) . '*/';
    }
    /**
     * Fetch recursive doc blocks from parent classes
     *
     * @param \ReflectionClass $reflectionClass
     * @param string           $methodName
     * @return array|void
     */
    private function fetch_recursive_doc_block_from_parent($reflection_class, $method_name)
    {
        $doc_comment = [];
        $parent_reflection_class = $reflection_class->get_parent_class();
        if (!$parent_reflection_class) {
            return;
        }
        if (!$parent_reflection_class->has_method($method_name)) {
            return;
        }
        $method_reflection = $parent_reflection_class->get_method($method_name);
        $doc_comment_last = $method_reflection->get_doc_comment();
        $doc_comment[] = $doc_comment_last;
        if ($this->is_inherit($doc_comment_last)) {
            if ($doc_comment_fetched = $this->fetch_recursive_doc_block_from_parent($parent_reflection_class, $method_name)) {
                $doc_comment = array_merge($doc_comment, $doc_comment_fetched);
            }
        }
        return $doc_comment;
    }
    /**
     * Return true if doc block inherit from parent or interface
     *
     * @param string $docComment
     */
    private function is_inherit($doc_comment): bool
    {
        return str_contains($doc_comment, self::INHERIT_TAG);
    }
}