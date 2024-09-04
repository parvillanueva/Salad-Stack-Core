<?php
namespace Salad\Core;

class Application
{

    public static Application $app;
    public static string $ROOT_DIR;
    public Response $response;
    public Request $request;
    public Session $session;
    public Extension $extension;
    public Database $db;
    
    public function __construct($rootDir)
    {
      self::$app = $this;
      self::$ROOT_DIR = $rootDir;
      $this->request = new Request();
      $this->response = new Response();
      $this->session = new Session();
      $this->extension = new Extension();
      $this->db = new Database();
    }
}
