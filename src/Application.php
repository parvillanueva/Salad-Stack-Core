<?php
namespace Lettuce\Core;

class Application
{

    public static Application $app;
    public static string $ROOT_DIR;
    
    public function __construct($rootDir)
    {
      self::$ROOT_DIR = $rootDir;
    }
}
