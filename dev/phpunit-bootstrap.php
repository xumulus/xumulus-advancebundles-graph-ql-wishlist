<?php
/**
 * PHPUnit bootstrap for the module's unit suite (phpunit.xml.dist). Repository-only, not shipped.
 *
 * Finds a Composer autoloader that provides magento/framework and PHPUnit, in this order:
 *   1. the XAB_AUTOLOAD environment variable (e.g. /var/www/html/vendor/autoload.php of a Magento install);
 *   2. the store's vendor/autoload.php when the module sits in app/code/Xumulus/AdvanceBundlesGraphQlWishlist;
 *   3. the store's vendor/autoload.php when the module is installed in vendor/xumulus/.
 * The main module, Xumulus_AdvanceBundles, and the GraphQL package, Xumulus_AdvanceBundlesGraphQl, come from that
 * install (app/code or vendor/), or from the checkouts named by the XAB_MAIN_MODULE and XAB_GRAPHQL_MODULE
 * environment variables. This checkout is mapped onto its namespace, so the tests always run against these files,
 * and Magento's unit-test generators are registered so tests can mock *Factory classes.
 */
declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Code\Generator\Io;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Phrase;
use Magento\Framework\Phrase\Renderer\Placeholder;
use Magento\Framework\TestFramework\Unit\Autoloader\ExtensionAttributesGenerator;
use Magento\Framework\TestFramework\Unit\Autoloader\ExtensionAttributesInterfaceGenerator;
use Magento\Framework\TestFramework\Unit\Autoloader\FactoryGenerator;
use Magento\Framework\TestFramework\Unit\Autoloader\GeneratedClassesAutoloader;

$moduleRoot = dirname(__DIR__);

$candidates = [
    (string)getenv('XAB_AUTOLOAD'),
    $moduleRoot . '/../../../../vendor/autoload.php',
    $moduleRoot . '/../../autoload.php',
];

$loader = null;
foreach ($candidates as $candidate) {
    if ($candidate !== '' && is_file($candidate)) {
        $loader = require $candidate;
        break;
    }
}

if (!$loader instanceof ClassLoader) {
    throw new RuntimeException(
        "No Composer autoloader with Magento found. Set XAB_AUTOLOAD to a Magento install's vendor/autoload.php."
    );
}

$loader->addPsr4('Xumulus\\AdvanceBundlesGraphQlWishlist\\', $moduleRoot . '/', true);
$checkouts = [
    'XAB_MAIN_MODULE' => 'Xumulus\\AdvanceBundles\\',
    'XAB_GRAPHQL_MODULE' => 'Xumulus\\AdvanceBundlesGraphQl\\',
];
foreach ($checkouts as $variable => $namespace) {
    $path = (string)getenv($variable);
    if ($path !== '') {
        $loader->addPsr4($namespace, rtrim($path, '/') . '/', true);
    }
}

if (!defined('TESTS_TEMP_DIR')) {
    define('TESTS_TEMP_DIR', $moduleRoot . '/.phpunit.cache');
}

$generatedCodeAutoloader = new GeneratedClassesAutoloader(
    [
        new ExtensionAttributesGenerator(),
        new ExtensionAttributesInterfaceGenerator(),
        new FactoryGenerator(),
    ],
    new Io(
        new File(),
        TESTS_TEMP_DIR . '/' . DirectoryList::getDefaultConfig()[DirectoryList::GENERATED_CODE][DirectoryList::PATH]
    )
);
spl_autoload_register([$generatedCodeAutoloader, 'load']);

Phrase::setRenderer(new Placeholder());

error_reporting(E_ALL);
