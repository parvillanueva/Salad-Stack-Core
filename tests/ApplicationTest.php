<?php

use Salad\Core\Application;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    protected function setUp(): void
    {
      Application::$app = new Application('/tmp');
      Application::$ROOT_DIR = '';
    }

    public function testApplicationInstanceAssignment()
    {
      $rootDir = '/var/www';
      $appInstance = new Application($rootDir);
      $this->assertEquals($rootDir, Application::$ROOT_DIR);
      Application::$app = $appInstance;
      $this->assertSame($appInstance, Application::$app);
    }

    public function testRootDirAssignment()
    {
      $rootDir = '/some/root/dir';
      $appInstance = new Application($rootDir);
      $this->assertEquals($rootDir, Application::$ROOT_DIR);
    }
}
