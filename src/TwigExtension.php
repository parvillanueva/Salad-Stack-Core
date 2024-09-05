<?php

namespace Salad\Core;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

use Salad\Core\Application;
use Salad\Core\Database;
use Salad\Core\BaseComponent;
use App\Models\User;

class TwigExtension extends AbstractExtension
{

    private $App;
    private $db;
    protected $user;

    public function __construct()
    {
      $this->App = Application::$app;
      $this->db = new Database();
      $this->user = new User;
    }
    
    public function getFunctions(): array
    {
      return [
        new TwigFunction('get_site_title', [$this, 'getSiteTitle']),
        new TwigFunction('get_site_description', [$this, 'getSiteDescription']),
        new TwigFunction('get_site_favicon', [$this, 'getSiteFavicon']),
        new TwigFunction('get_base_url', [$this, 'getBaseUrl']),
        new TwigFunction('get_flash_message', [$this, 'getFlashMessage']),
        new TwigFunction('get_extensions', [$this, 'getExtensions']),
        new TwigFunction('check_extension_enabled', [$this, 'checkExtensionEnabled']),
        new TwigFunction('get_logged_email', [$this, 'getLoggedEmail']),
        new TwigFunction('get_request_uri', [$this, 'getRequestURI']),
        new TwigFunction('render_component', [$this, 'renderComponent']),
        new TwigFunction('parse_value', [$this, 'parseTableValue']),
        new TwigFunction('get_update_form', [$this, 'getUpdateForm']),
      ];
    }

    public function getFilters(): array
    {
      return [
        new TwigFilter('uppercase', [$this, 'toUppercase']),
        new TwigFilter('base_url', [$this, 'baseUrl']),
        new TwigFilter('css', [$this, 'renderCSS']),
      ];
    }
    

    public function getFlashMessage($key)
    {
      return Application::$app->session->getFlash($key);
    }

    public function parseTableValue($key, $value)
    {
      if($key == 'status'){
        return $value == 1? "Visible":"Hidden";
      }
      if($this->isValidImageUrl($value)){
        return "<img src='$value' width='200' />";
      }
      return $value;
    }
    
    function isValidImageUrl($url) {
      $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
      $extension = pathinfo($url, PATHINFO_EXTENSION);

      return in_array(strtolower($extension), $imageExtensions);
  }
    

    public function renderCSS($css_path)
    {
      $inlineCss = file_get_contents($css_path);

      return "<style>$inlineCss</style>";
    }

    public function getLoggedEmail()
    {
      $userId = $this->App->session->get('user_id');
      $stmt = $this->user->findById($userId);
      return $stmt['email'];
    }

    public function getUpdateForm($table, $id)
    {
      return $this->App->extension->getUpdateForm($table, false, $id);
    }

    public function getRequestURI()
    {
      return $_SERVER['REQUEST_URI'];
    }

    public function getExtensions($type): array
    {
      return Application::$app->extension->getExtensions()[$type];
    }

    public function getSiteTitle(): string
    {
      $stmt = $this->db->fetch("SELECT title FROM site_basic_settings WHERE id = :id", [':id' => 1]);
      return $stmt['title'] ?? "SaladStack";
    }

    public function getSiteDescription(): string
    {
      $stmt = $this->db->fetch("SELECT description FROM site_basic_settings WHERE id = :id", [':id' => 1]);
      return $stmt['description'] ?? "SaladStack";
    }

    public function getSiteFavicon(): string
    {
      $stmt = $this->db->fetch("SELECT favicon FROM site_basic_settings WHERE id = :id", [':id' => 1]);
      return $stmt['favicon'] ?? "SaladStack";
    }

    public function getBaseUrl() {
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      $hostName = $_SERVER['HTTP_HOST'];
      $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
      $baseUrl = $protocol . $hostName . $scriptPath;
      return rtrim($baseUrl, '/');
    }

    public function toUppercase(string $text): string
    {
      return strtoupper($text);
    }

    public function baseUrl(string $url): string
    {
      return $this->getBaseUrl() . $url;
    }

    public function checkExtensionEnabled(string $name): string
    {

      $name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $name));
      try {
        if(isset($_ENV['EXTENSION_' . $name]) && $_ENV['EXTENSION_' . $name] === 'true'){
          return true;
        }
        return false;
      } catch (\Throwable $th) {
        return false;
      }
    }

    

}
