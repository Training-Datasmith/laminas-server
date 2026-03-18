<?php

declare(strict_types=1);

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
class ReflectionMethod extends AbstractFunction
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
    public function __construct(/**
     * Parent class reflection
     */
        protected \Laminas\Server\Reflection\ReflectionClass $classReflection,
        \ReflectionMethod $r,
        $namespace = null,
        $argv = []
    ) {
        $this->reflection      = $r;

        $classNamespace = $this->classReflection->getNamespace();

        // Determine namespace
        if (! empty($namespace)) {
            $this->setNamespace($namespace);
        } elseif (! empty($classNamespace)) {
            $this->setNamespace($classNamespace);
        }

        // Determine arguments
        $this->argv = $argv;

        // If method call, need to store some info on the class
        $this->class = $this->classReflection->getName();
        $this->name  = $r->getName();

        // Perform some introspection
        $this->reflect();
    }

    /**
     * Return the reflection for the class that defines this method
     *
     * @return ReflectionClass|\ReflectionClass
     */
    public function getDeclaringClass()
    {
        return $this->classReflection;
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
        $this->name  = $data['name'];

        $this->classReflection = new ReflectionClass(
            new \ReflectionClass($this->class),
            $this->getNamespace(),
            $this->getInvokeArguments()
        );

        $this->reflection = new \ReflectionMethod($this->classReflection->getName(), $this->name);
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    #[Override]
    protected function reflect()
    {
        $docComment = $this->reflection->getDocComment();
        if (str_contains($docComment, self::INHERIT_TAG)) {
            $this->docComment = $this->fetchRecursiveDocComment();
        }

        parent::reflect();
    }

    /**
     * Fetch all doc comments for inherit values
     */
    private function fetchRecursiveDocComment(): string
    {
        $currentMethodName = $this->reflection->getName();
        $docCommentList[]  = $this->reflection->getDocComment();

        // fetch all doc blocks for method from parent classes
        $docCommentFetched = $this->fetchRecursiveDocBlockFromParent($this->classReflection, $currentMethodName);
        if ($docCommentFetched) {
            $docCommentList = array_merge($docCommentList, $docCommentFetched);
        }

        // fetch doc blocks from interfaces
        $interfaceReflectionList = $this->classReflection->getInterfaces();
        foreach ($interfaceReflectionList as $interfaceReflection) {
            if (! $interfaceReflection->hasMethod($currentMethodName)) {
                continue;
            }

            $docCommentList[] = $interfaceReflection->getMethod($currentMethodName)->getDocComment();
        }

        $normalizedDocCommentList = array_map(
            function ($docComment): string|array {
                $docComment = str_replace('/**', '', $docComment);

                return str_replace('*/', '', $docComment);
            },
            $docCommentList
        );

        return '/**' . implode(PHP_EOL, $normalizedDocCommentList) . '*/';
    }

    /**
     * Fetch recursive doc blocks from parent classes
     *
     * @param \ReflectionClass $reflectionClass
     * @param string           $methodName
     * @return array|void
     */
    private function fetchRecursiveDocBlockFromParent($reflectionClass, $methodName)
    {
        $docComment            = [];
        $parentReflectionClass = $reflectionClass->getParentClass();
        if (! $parentReflectionClass) {
            return;
        }

        if (! $parentReflectionClass->hasMethod($methodName)) {
            return;
        }

        $methodReflection = $parentReflectionClass->getMethod($methodName);
        $docCommentLast   = $methodReflection->getDocComment();
        $docComment[]     = $docCommentLast;
        if ($this->isInherit($docCommentLast)) {
            if ($docCommentFetched = $this->fetchRecursiveDocBlockFromParent($parentReflectionClass, $methodName)) {
                $docComment = array_merge($docComment, $docCommentFetched);
            }
        }

        return $docComment;
    }

    /**
     * Return true if doc block inherit from parent or interface
     *
     * @param string $docComment
     */
    private function isInherit($docComment): bool
    {
        return str_contains($docComment, self::INHERIT_TAG);
    }
}
