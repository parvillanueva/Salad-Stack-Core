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
    public FileUploader $uploader;
    
    public function __construct($rootDir)
    {
      self::$app = $this;
      self::$ROOT_DIR = $rootDir;
      $this->request = new Request();
      $this->response = new Response();
      $this->session = new Session();
      $this->uploader = new FileUploader();
      $this->extension = new Extension();
      $this->db = new Database();
    }

    public function getBaseUrl() {
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      $hostName = $_SERVER['HTTP_HOST'];
      $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
      $baseUrl = $protocol . $hostName . $scriptPath;
      return rtrim($baseUrl, '/');
    }
}
