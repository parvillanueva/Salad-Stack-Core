<?php
namespace Salad\Core;

class Application
{

    public static Application $app;
    public static string $ROOT_DIR;
    public Response $response;
    public Session $session;
    
    public function __construct($rootDir)
    {
      self::$app = $this;
      $this->response = new Response();
      $this->session = new Session();
      self::$ROOT_DIR = $rootDir;
    }
}
