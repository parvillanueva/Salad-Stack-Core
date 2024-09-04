<?php

namespace Salad\Core;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

use Salad\Core\Application;
use Salad\Core\Database;

class TwigExtension extends AbstractExtension
{

    private $db;

    public function __construct()
    {
      $this->db = new Database();
    }
    
    public function getFunctions(): array
    {
      return [
        new TwigFunction('get_site_title', [$this, 'getSiteTitle']),
        new TwigFunction('get_site_description', [$this, 'getSiteDescription']),
        new TwigFunction('get_site_favicon', [$this, 'getSiteFavicon']),
        new TwigFunction('get_base_url', [$this, 'getBaseUrl']),
      ];
    }

    public function getFilters(): array
    {
      return [
        new TwigFilter('uppercase', [$this, 'toUppercase']),
        new TwigFilter('base_url', [$this, 'baseUrl']),
      ];
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

    

}
