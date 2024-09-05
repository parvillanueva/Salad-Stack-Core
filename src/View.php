<?php
namespace Salad\Core;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Salad\Core\TwigExtension;

class View
{
    protected $twig;
    protected $App;
    protected $extension_path;

    public function __construct()
    {
        $this->App = Application::$app;
        $this->extension_path = [
            Application::$ROOT_DIR . '/src/Views'
        ];
        $this->initTwig();
    }

    function initTwig() {
        $loader = new FilesystemLoader($this->extension_path);
        $this->twig = new Environment($loader, [
            'cache' => false,
        ]);
        $this->twig->addExtension(new TwigExtension());
        $this->twig->addFunction(new \Twig\TwigFunction('asset', function ($path) {
            return Application::$app->getBaseUrl() ."/" . ltrim($path, '/');
        }));
    }

    public function addViewPath($path)
    {
        array_push(
            $this->extension_path,
            $path
        );
        $this->initTwig();
    }

    public function setPostLogin()
    {
        $userId = $this->App->session->get('user_id');
        if(!$userId){
            $this->App->response->redirect("/admin/login");
        }
    }

    public function getTwigEnv() {
        return $this->twig;
    }

    public function addTwixExtension($extension_path) {
        $this->twig->addExtension($extension_path);
    }

    public function render($view, $data = [])
    {
        echo $this->twig->render($view . '.twig', $data);
    }
}
