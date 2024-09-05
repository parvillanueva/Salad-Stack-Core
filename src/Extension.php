<?php
namespace Salad\Core;

use Symfony\Component\Yaml\Yaml;


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
        if($package['type'] == "salad-extension" || $package['type'] == "salad-section"){
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


  public function getTable($table, $search = null){
    return Application::$app->db->fetchAll("SELECT * FROM $table");
  }

  public function getType($package_name){
    $package = $this->getFeature($package_name);

    $package_path = Application::$ROOT_DIR ."/vendor/" . $this->normalizePath($package['install-path'] . "/forms/admin.yml");
    $formConfig = Yaml::parseFile($package_path);
    return $formConfig['form']['type'];
  }

  public function getForm($package_name, $admin = false, $id = null){
    $package = $this->getFeature($package_name);

    $package_path = Application::$ROOT_DIR ."/vendor/" . $this->normalizePath($package['install-path'] . "/forms/admin.yml");
    $formConfig = Yaml::parseFile($package_path);
    return [
      "type" => $formConfig['form']['type'],
      "title" => $formConfig['form']['title'],
      "table" => $formConfig['form']['table'],
      "form" => $this->generateFormFromYaml($package_path, $id)
    ];
  }

  public function getUpdateForm($package_name, $admin = false, $id = null){
    $package = $this->getFeature($package_name);

    $package_path = Application::$ROOT_DIR ."/vendor/" . $this->normalizePath($package['install-path'] . "/forms/admin.yml");
    $formConfig = Yaml::parseFile($package_path);
    return [
      "type" => $formConfig['form']['type'],
      "title" => "Update" . $formConfig['form']['title'],
      "table" => $formConfig['form']['table'],
      "form" => $this->generateFormFromYaml($package_path, $id)
    ];
  }


  public function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $hostName = $_SERVER['HTTP_HOST'];
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl = $protocol . $hostName . $scriptPath;
    return rtrim($baseUrl, '/');
  }


  function normalizePath($path) {
    $parts = explode('/', $path);
    $stack = [];

    foreach ($parts as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        
        if ($part === '..') {
            if (!empty($stack)) {
                array_pop($stack);
            }
        } else {
            $stack[] = $part;
        }
    }
    return implode('/', $stack);
  }

  function getFormValue($table, $field, $id = null) {
    if($id){
      $stmt = Application::$app->db->fetch("SELECT $field FROM $table WHERE id = $id");
      return $stmt[$field] ?? null;
    } 
    return null;
  }

  function generateFormFromYaml($yamlFile, $id = null)
  {
    // Parse the YAML file
    $formConfig = Yaml::parseFile($yamlFile);
    $table = $formConfig['form']['table'];

    
    // Start building the form HTML
    $formHtml = '<form action="'. $this->getBaseUrl().'/admin/extension/form-submit" method="POST"  enctype="multipart/form-data">';
    if(!$id){
      $formHtml .= '<h2>' . $formConfig['form']['title'] . '</h2>';
      $formHtml .= '<p>' . $formConfig['form']['description'] . '</p>';
    }
    $formHtml .= '<input type="text" name="table" hidden value="' . $formConfig['form']['table'] . '" />';
    $formHtml .= '<input type="text" name="type" hidden value="' . $formConfig['form']['type'] . '" />';
    if($id){
      $formHtml .= '<input type="text" name="id" hidden value="' . $id . '" />';
    }


    // Loop through the fields and generate HTML elements
    foreach ($formConfig['form']['fields'] as $field) {
        $formHtml .= '<div class="form-group">';
        $formHtml .= '<label for="' . $field['name'] . '">' . $field['label'] . '</label>';

        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'password':
                $formHtml .= '<input type="' . $field['type'] . '" placeholder="' . $field['placeholder'] . '" name="' . $field['name'] . '" id="' . $field['name'] . '"';
                if (!empty($field['required'])) {
                    $formHtml .= ' required';
                }
                $formHtml .= ' value="' . $this->getFormValue($table, $field['name'], $id) .  '" ';
                $formHtml .= ' class="form-control">';
                break;
            case 'file':
            case 'upload':
              $formHtml .= '<input type="file" name="' . $field['name'] . '" placeholder="' . $field['placeholder'] . '" id="' . $field['name'] . '"';
              if (!empty($field['required'])) {
                  $formHtml .= ' required';
              }
              $formHtml .= ' accept="image/*" value="' . $this->getFormValue($table, $field['name'], $id) .  '" ';
              $formHtml .= ' class="form-control">';
              break;

            case 'select':
                $formHtml .= '<select name="' . $field['name'] . '" id="' . $field['name'] . '" placeholder="' . $field['placeholder'] . '" class="form-control">';
                foreach ($field['options'] as $option) {
                    $formHtml .= '<option value="' . htmlspecialchars($option['value']) . '"  ' . ($this->getFormValue($table, $field['name'], $id) == htmlspecialchars($option['value']) ? "selected" : "") . '>' . htmlspecialchars($option['label']) . '</option>';
                }
                $formHtml .= '</select>';
                break;

            case 'radio':
                foreach ($field['options'] as $option) {
                    $formHtml .= '<div class="form-check">';
                    $formHtml .= '<input type="radio" ' . ($this->getFormValue($table, $field['name'], $id) == htmlspecialchars($option['value']) ? "checked" : "") . ' name="' . $field['name'] . '" placeholder="' . $field['placeholder'] . '" id="' . $field['name'] . '_' . htmlspecialchars($option['value']) . '" value="' . htmlspecialchars($option['value']) . '"';
                    if (!empty($field['required'])) {
                      $formHtml .= ' required';
                    }
                    $formHtml .= ' class="form-check-input">';
                    $formHtml .= '<label for="' . $field['name'] . '_' . htmlspecialchars($option['value']) . '" class="form-check-label">' . htmlspecialchars($option['label']) . '</label>';
                    $formHtml .= '</div>';
                }
                break;

            case 'checkbox':
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $option) {
                        $formHtml .= '<div class="form-check">';
                        $formHtml .= '<input ' . ($this->getFormValue($table, $field['name'], $id) == htmlspecialchars($option['value']) ? "checked" : "") . ' type="checkbox" name="' . $field['name'] . '[]" id="' . $field['name'] . '" placeholder="' . $field['placeholder'] . '_' . htmlspecialchars($option) . '" value="' . htmlspecialchars($option) . '" class="form-check-input">';
                        $formHtml .= '<label for="' . $field['name'] . '_' . htmlspecialchars($option) . '" class="form-check-label">' . htmlspecialchars($option) . '</label>';
                        $formHtml .= '</div>';
                    }
                } else {
                    $formHtml .= '<input type="checkbox" ' . ($this->getFormValue($table, $field['name'], $id) == htmlspecialchars($option['value']) ? "checked" : "") . ' name="' . $field['name'] . '" id="' . $field['name'] . '"';
                    if (!empty($field['required'])) {
                        $formHtml .= ' required';
                    }
                    $formHtml .= ' class="form-check-input">';
                }
                break;
        }

        $formHtml .= '</div>';
    }

    // Close the form and add a submit button
    $formHtml .= '<button type="submit" class="btn btn-primary">Submit</button>';
    $formHtml .= '</form>';

    return $formHtml;
}

}
