<?php
namespace Salad\Core;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Salad\Core\TwigExtension;

class View
{
    protected $twig;

    public function __construct()
    {
        define('BASE_URL', Application::$ROOT_DIR . '/public/');

        $loader = new FilesystemLoader(Application::$ROOT_DIR . '/src/Views');
        $this->twig = new Environment($loader, [
            'cache' => false,
        ]);
        $this->twig ->addExtension(new TwigExtension());
        $this->twig->addFunction(new \Twig\TwigFunction('asset', function ($path) {
            return $this->getBaseUrl() ."/" . ltrim($path, '/');
        }));
    }

    function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $hostName = $_SERVER['HTTP_HOST'];
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        $baseUrl = $protocol . $hostName . $scriptPath;
        return rtrim($baseUrl, '/');
    }

    public function render($view, $data = [])
    {
        echo $this->twig->render($view . '.twig', $data);
    }
}
