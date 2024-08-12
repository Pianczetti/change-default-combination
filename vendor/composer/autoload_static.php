<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0d4fe71a35185b48c148fdbddbf257b5
{
    public static $prefixLengthsPsr4 = array (
        '<' => 
        array (
            '<YourNamespace>\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        '<YourNamespace>\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit0d4fe71a35185b48c148fdbddbf257b5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit0d4fe71a35185b48c148fdbddbf257b5::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit0d4fe71a35185b48c148fdbddbf257b5::$classMap;

        }, null, ClassLoader::class);
    }
}