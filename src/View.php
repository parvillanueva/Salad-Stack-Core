<?php
namespace Lettuce\Core;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class View
{
    protected $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(Application::$ROOT_DIR . '/src/Views');
        $this->twig = new Environment($loader, [
            'cache' => false,
        ]);
    }

    public function render($view, $data = [])
    {
        echo $this->twig->render($view . '.twig', $data);
    }
}
