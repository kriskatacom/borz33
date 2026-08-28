<?php

declare(strict_types=1);

namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Factory;

class ValidatorFactory
{
    private static ?Factory $factory = null;

    public static function make(): Factory
    {
        if (self::$factory instanceof Factory) {
            return self::$factory;
        }

        $langPath = dirname(__DIR__, 3) . '/lang';
        $loader = new FileLoader(new Filesystem(), $langPath);
        $translator = new Translator($loader, 'bg');
        $factory = new Factory($translator);

        $connections = new ConnectionResolver([
            'default' => Capsule::connection(),
        ]);
        $connections->setDefaultConnection('default');
        $factory->setPresenceVerifier(new DatabasePresenceVerifier($connections));

        self::$factory = $factory;

        return $factory;
    }
}
