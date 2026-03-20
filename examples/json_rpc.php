<?php

declare(strict_types=1);

/**
 * Example: JSON-RPC server with laminas-server.
 *
 * Run from the laminas-server project root:
 *   php examples/json_rpc.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Laminas\Server\Method\Definition;
use Laminas\Server\Method\Parameter;
use Laminas\Server\Reflection\Reflection;

// --- Define a service class for the server ---
class Calculator
{
    /**
     * Add two numbers together.
     *
     * @param  float $a First operand.
     * @param  float $b Second operand.
     * @return float    The sum.
     */
    public function add(float $a, float $b): float
    {
        return $a + $b;
    }

    /**
     * Multiply two numbers.
     *
     * @param  float $a First operand.
     * @param  float $b Second operand.
     * @return float    The product.
     */
    public function multiply(float $a, float $b): float
    {
        return $a * $b;
    }

    /**
     * Check if a number is even.
     *
     * @param  int  $n The integer to check.
     * @return bool    True if $n is even.
     */
    public function is_even(int $n): bool
    {
        return ($n % 2) === 0;
    }
}

// --- Reflect on the class to extract method signatures ---
$reflectionMethods = Reflection::reflectClass(new Calculator());

echo "Methods available via laminas-server reflection:\n";
foreach ($reflectionMethods as $method) {
    $params     = $method->getParameters();
    $paramNames = array_map(fn($p) => '$' . $p->getName(), $params);
    $returns    = $method->getReturnType() ?? 'mixed';

    printf(
        "  %-15s (%s): %s\n",
        $method->getName(),
        implode(', ', $paramNames),
        $returns
    );
}

echo "\n";

// --- Build a Method\Definition manually ---
$def = new Definition(['name' => 'add']);

$paramA = new Parameter(['name' => 'a', 'type' => 'float', 'position' => 0]);
$paramB = new Parameter(['name' => 'b', 'type' => 'float', 'position' => 1]);

$def->addParameter($paramA);
$def->addParameter($paramB);

echo "Method definition name: " . $def->getName() . "\n";
echo "Parameter count:        " . count($def->getParameters()) . "\n";
