<?php
/**
 * User: kit
 * Date: 01/01/15
 * Time: 12:13
 */

spl_autoload_register(function ($class)
{
    // project-specific namespace prefix
    $prefix = 'CodingGuys\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0)
    {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file))
    {
        require $file;
    }
});