<?php
/**
 * PSR-4 Autoloader for GM_HMS
 */

spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'GM_HMS\\';

    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/../';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    
    // Mapping specific namespaces to directories if they don't follow PSR-4 exactly
    // e.g. controler/ instead of Controllers/
    $map = [
        'Controllers\\' => 'controler/',
        'Models\\' => 'models/',
        'Core\\' => 'core/',
        'Middleware\\' => 'middleware/',
        'Security\\' => 'security/',
        'Database\\' => 'Database/',
        'Config\\' => 'config/',
        'Modules\\' => 'modules/'
    ];

    foreach ($map as $ns => $dir) {
        if (strncmp($ns, $relative_class, strlen($ns)) === 0) {
            $file = $base_dir . $dir . str_replace('\\', '/', substr($relative_class, strlen($ns))) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }

    // Fallback to standard PSR-4 mapping
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
