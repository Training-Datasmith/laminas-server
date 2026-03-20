<?php

declare (strict_types=1);
/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 */
namespace Laminas\Server;

use function array_keys;
use function dirname;
use const E_NOTICE;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function is_readable;
use function is_string;
use function is_writable;
use Laminas\Stdlib\Error_Handler;
use function serialize;
use function unlink;
use function unserialize;
/**
 * \Laminas\Server\Cache: cache server definitions
 *
 * @final This class should not be extended
 */
class Cache
{
    /** @var array Methods to skip when caching server */
    protected static $skip_methods = [];
    /**
     * Cache a file containing the dispatch list.
     *
     * Serializes the server definition stores the information
     * in $filename.
     *
     * Returns false on any error (typically, inability to write to file), true
     * on success.
     *
     * @param  string $filename
     */
    public static function save($filename, Server $server): bool
    {
        if (!is_string($filename) || !file_exists($filename) && !is_writable(dirname($filename))) {
            return false;
        }
        $methods = self::create_definition($server->get_functions());
        Error_Handler::start();
        $test = file_put_contents($filename, serialize($methods));
        Error_Handler::stop();
        if (0 === $test) {
            return false;
        }
        return true;
    }
    /**
     * Load server definition from a file
     *
     * Unserializes a stored server definition from $filename. Returns false if
     * it fails in any way, true on success.
     *
     * Useful to prevent needing to build the server definition on each
     * request. Sample usage:
     *
     * <code>
     * if (!Laminas\Server\Cache::get($filename, $server)) {
     *     require_once 'Some/Service/ServiceClass.php';
     *     require_once 'Another/Service/ServiceClass.php';
     *
     *     // Attach Some\Service\ServiceClass with namespace 'some'
     *     $server->attach('Some\Service\ServiceClass', 'some');
     *
     *     // Attach Another\Service\ServiceClass with namespace 'another'
     *     $server->attach('Another\Service\ServiceClass', 'another');
     *
     *     Laminas\Server\Cache::save($filename, $server);
     * }
     *
     * $response = $server->handle();
     * echo $response;
     * </code>
     *
     * @param  string $filename
     */
    public static function get($filename, Server $server): bool
    {
        if (!is_string($filename) || !file_exists($filename) || !is_readable($filename)) {
            return false;
        }
        Error_Handler::start();
        $dispatch = file_get_contents($filename);
        Error_Handler::stop();
        if (false === $dispatch) {
            return false;
        }
        Error_Handler::start(E_NOTICE);
        $dispatch_array = unserialize($dispatch);
        Error_Handler::stop();
        if (false === $dispatch_array) {
            return false;
        }
        $server->load_functions($dispatch_array);
        return true;
    }
    /**
     * Remove a cache file
     *
     * @param  string $filename
     */
    public static function delete($filename): bool
    {
        if (file_exists($filename)) {
            unlink($filename);
            return true;
        }
        return false;
    }
    /**
     * @param array|Definition $methods
     * @return array|Definition
     */
    private static function create_definition($methods)
    {
        if ($methods instanceof Definition) {
            return self::create_definition_from_methods_definition($methods);
        }
        return self::create_definition_from_methods_array($methods);
    }
    private static function create_definition_from_methods_definition(Definition $methods): \Laminas\Server\Definition
    {
        $definition = new Definition();
        foreach ($methods as $method) {
            if (in_array($method->get_name(), static::$skip_methods, true)) {
                continue;
            }
            $definition->add_method($method);
        }
        return $definition;
    }
    private static function create_definition_from_methods_array(array $methods): array
    {
        foreach (array_keys($methods) as $method_name) {
            if (in_array($method_name, static::$skip_methods, true)) {
                unset($methods[$method_name]);
            }
        }
        return $methods;
    }
}