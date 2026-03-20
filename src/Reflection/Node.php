<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server\Reflection;

use function array_merge;
use function count;
/**
 * Node Tree class for Laminas\Server reflection operations
 */
class Node
{
    /**
     * Array of child nodes (if any)
     *
     * @var array
     */
    protected $children = [];
    /**
     * Parent node (if any)
     *
     * @var Node
     */
    protected $parent;
    /**
     * Constructor
     *
     * @param mixed $value
     * @param Node $parent Optional
     * @return Node
     */
    public function __construct(
        /**
         * Node value
         */
        protected $value,
        ?Node $parent = null
    )
    {
        if (null !== $parent) {
            $this->set_parent($parent, true);
        }
        return $this;
    }
    /**
     * Set parent node
     *
     * //phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
     * //phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.UselessAnnotation
     *
     * @param \Laminas\Server\Reflection\Node $node
     * //phpcs:enable SlevomatCodingStandard.TypeHints.ParameterTypeHint.UselessAnnotation
     * //phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
     * @param  bool $new Whether or not the child node is newly created
     * and should always be attached
     */
    public function set_parent(Node $node, $new = false): void
    {
        $this->parent = $node;
        if ($new) {
            $node->attach_child($this);
            return;
        }
    }
    /**
     * Create and attach a new child node
     *
     * @param mixed $value
     * @access public
     * @return Node New child node
     */
    public function create_child($value): static
    {
        return new static($value, $this);
    }
    /**
     * Attach a child node
     */
    public function attach_child(Node $node): void
    {
        $this->children[] = $node;
        if ($node->get_parent() !== $this) {
            $node->set_parent($this);
        }
    }
    /**
     * Return an array of all child nodes
     *
     * @return array
     */
    public function get_children()
    {
        return $this->children;
    }
    /**
     * Does this node have children?
     */
    public function has_children(): bool
    {
        return count($this->children) > 0;
    }
    /**
     * Return the parent node
     *
     * @return null|Node
     */
    public function get_parent()
    {
        return $this->parent;
    }
    /**
     * Return the node's current value
     *
     * @return mixed
     */
    public function get_value()
    {
        return $this->value;
    }
    /**
     * Set the node value
     *
     * @param mixed $value
     */
    public function set_value($value): void
    {
        $this->value = $value;
    }
    /**
     * Retrieve the bottommost nodes of this node's tree
     *
     * Retrieves the bottommost nodes of the tree by recursively calling
     * getEndPoints() on all children. If a child is null, it returns the parent
     * as an end point.
     */
    public function get_end_points(): array
    {
        $end_points = [];
        if (!$this->has_children()) {
            return $end_points;
        }
        foreach ($this->children as $child) {
            $value = $child->get_value();
            if (null === $value) {
                $end_points[] = $this;
            } elseif ($child->has_children()) {
                $child_end_points = $child->get_end_points();
                if (!empty($child_end_points)) {
                    $end_points = array_merge($end_points, $child_end_points);
                }
            } elseif (!$child->has_children()) {
                $end_points[] = $child;
            }
        }
        return $end_points;
    }
}