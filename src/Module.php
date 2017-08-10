<?php
declare(strict_types=1);

namespace Zend\Ftp;

/**
 *
 * @author ventimiglia Samuel
 *
 */
class Module
{
    /**
     * Return default zend-ftp configuration for zend-mvc context.
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return [
                'Zend\Loader\StandardAutoloader' => [
                        'namespaces' => [
                                // if we're in a namespace deeper than one level we need to fix the \ in the path
                                __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\', '/', __NAMESPACE__)
                        ]
                ]
        ];
    }
}
