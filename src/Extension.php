<?php
namespace Salad\Core;

class Extension
{
    protected $VENDOR_DIR;
    protected $features;
    protected $themes;

    public function __construct()
    {
      $this->VENDOR_DIR = Application::$ROOT_DIR . "/vendor/";
      $this->features = [];
      $this->themes = [];

      $this->getFeatures();
      $this->getThemes();
    }

    public function getFeatures()
    {
      $installed = json_decode(file_get_contents($this->VENDOR_DIR . "/composer/installed.json"), true);
      foreach ($installed['packages'] as $package) {
        if($package['type'] == "salad-extension"){
          if($package['extra']['salad-extension']['category'] == 'feature'){
            $this->features[] = $package;
          }
        }
      }
    }

    public function getThemes()
    {
      $installed = json_decode(file_get_contents($this->VENDOR_DIR . "/composer/installed.json"), true);
      foreach ($installed['packages'] as $package) {
        if($package['type'] == "salad-extension"){
          if($package['extra']['salad-extension']['category'] == 'theme'){
            $this->themes[] = $package;
          }
        }
      }
    }

    public function getExtensions()
    {
      return [
        "features" => $this->features,
        "themes" => $this->themes,
      ];
    }

    public function getFeatureList()
    {
      return $this->features;
    }

    public function getFeature($package)
    {
      foreach ($this->features as $object) {
        if ($object['name'] === $package) {
            return $object;
            break;
        }
      }
    }

}
