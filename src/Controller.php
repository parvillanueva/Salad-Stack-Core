<?php
namespace Lettuce\Core;

class Controller
{
  protected $view;

  public function __construct()
  {
      $this->view = new View();
  }

  protected function render($view, $data = [])
  {
      $this->view->render($view, $data);
  }

}
