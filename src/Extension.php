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

	public function getSections()
	{
		$sections = [];
		$installed = json_decode(file_get_contents($this->VENDOR_DIR . "/composer/installed.json"), true);
		foreach ($installed['packages'] as $package) {
			if($package['type'] == "salad-section"){
				if($package['extra']['salad-extension']['category'] == 'feature'){
					//check if active
					if($this->checkExtensionEnabled($package['name'])){
						$sections[] = [
							"id" => $package['name'],
							"title" => $package['extra']['salad-extension']['title'],
						];
					}
				}
			}
		}
		return $sections;
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

	public function getTable(string $table): array
	{
		return Application::$app->db->fetchAll("SELECT * FROM $table");
	}

	public function getType(string $packageName): string
    {
        $formConfig = $this->getFormConfig($packageName);
        return $formConfig['form']['type'] ?? '';
    }

	private function getFormConfig(string $packageName): array
    {
        $package = $this->getFeature($packageName);
        $packagePath = $this->VENDOR_DIR . Application::$app->normalizePath($package['install-path'] . "/src/Forms/admin.yml");
        return Yaml::parseFile($packagePath);
    }

	public function getForm(string $package_name, bool $admin = false, ?int $id = null): array
	{
		$package = $this->getFeature($package_name);

		$package_path = Application::$ROOT_DIR ."/vendor/" . Application::$app->normalizePath($package['install-path'] . "/src/Forms/admin.yml");
		$formConfig = Yaml::parseFile($package_path);
		return [
			"type" => $formConfig['form']['type'],
			"title" => $formConfig['form']['title'],
			"table" => $formConfig['form']['table'],
			"form" => $this->generateFormFromYaml($package_path, $id)
		];
	}

	public function getUpdateForm($package_name, $admin = false, $id = null): array
	{
		$package = $this->getFeature($package_name);

		$package_path = Application::$ROOT_DIR ."/vendor/" . Application::$app->normalizePath($package['install-path'] . "/src/Forms/admin.yml");
		$formConfig = Yaml::parseFile($package_path);
		return [
			"type" => $formConfig['form']['type'],
			"title" => "Update" . $formConfig['form']['title'],
			"table" => $formConfig['form']['table'],
			"form" => $this->generateFormFromYaml($package_path, $id)
		];
	}

	public function getFormValue(string $table, string $field, ?int $id = null): ?string
    {
        if ($id) {
            $stmt = Application::$app->db->fetch("SELECT $field FROM $table WHERE id = ?", [$id]);
            return $stmt[$field] ?? null;
        }
        return null;
    }

  public function generateFormFromYaml(string $yamlFile, ?int $id = null): string
	{
		$formConfig = Yaml::parseFile($yamlFile);
		$table = $formConfig['form']['table'];

		$formHtml = '<form action="' . Application::$app->getBaseUrl() . '/admin/extension/form-submit" method="POST" enctype="multipart/form-data">';
		$formHtml .= $this->generateFormFields($formConfig['form'], $table, $id);
		$formHtml .= '<button type="submit" class="btn btn-primary">Submit</button>';
		$formHtml .= '</form>';

		return $formHtml;
	}

	private function generateFormFields(array $formConfig, string $table, ?int $id = null): string
	{
		$formHtml = '';
		
		if (!$id) {
			$formHtml .= '<h2>' . htmlspecialchars($formConfig['title']) . '</h2>';
			$formHtml .= '<p>' . htmlspecialchars($formConfig['description']) . '</p>';
		}

		$formHtml .= '<input type="hidden" name="table" value="' . htmlspecialchars($formConfig['table']) . '">';
		$formHtml .= '<input type="hidden" name="type" value="' . htmlspecialchars($formConfig['type']) . '">';

		if ($id) {
			$formHtml .= '<input type="hidden" name="id" value="' . htmlspecialchars($id) . '">';
		}

		foreach ($formConfig['fields'] as $field) {
			$formHtml .= $this->generateFieldHtml($field, $table, $id);
		}

		return $formHtml;
	}

	private function generateFieldHtml(array $field, string $table, ?int $id): string
	{
		$value = $this->getFormValue($table, $field['name'], $id);
		$html = '<div class="form-group">';
		$html .= '<label for="' . htmlspecialchars($field['name']) . '">' . htmlspecialchars($field['label']) . '</label>';

		switch ($field['type']) {
			case 'text':
			case 'email':
			case 'password':
				$html .= '<input type="' . htmlspecialchars($field['type']) . '" name="' . htmlspecialchars($field['name']) . '" placeholder="' . $field['placeholder'] . '" value="' . $value . '" class="form-control"' . ($field['required'] ? ' required' : '') . '>';
				break;
			case 'file':
			case 'upload':
				$html .= '<input type="file" name="' . htmlspecialchars($field['name']) . '" placeholder="' . $field['placeholder'] . '" class="form-control"' . ($field['required'] ? ' required' : '') . ' accept="image/*" value="' . $value . '">';
				break;
			case 'select':
				$html .= $this->generateSelectHtml($field, $value);
				break;
			case 'radio':
				$html .= $this->generateRadioHtml($field, $value);
				break;
			case 'checkbox':
				$html .= $this->generateCheckboxHtml($field, $value);
				break;
		}

		$html .= '</div>';
		return $html;
	}

	private function generateSelectHtml(array $field, ?string $value): string
	{
		$html = '<select name="' . htmlspecialchars($field['name']) . '" id="' . htmlspecialchars($field['name']) . '" class="form-control">';
		foreach ($field['options'] as $option) {
			$html .= '<option value="' . htmlspecialchars($option['value']) . '" ' . ($value === $option['value'] ? 'selected' : '') . '>' . htmlspecialchars($option['label']) . '</option>';
		}
		$html .= '</select>';
		return $html;
	}

	private function generateRadioHtml(array $field, ?string $value): string
	{
		$html = '';
		foreach ($field['options'] as $option) {
			$html .= '<div class="form-check">';
			$html .= '<input type="radio" name="' . htmlspecialchars($field['name']) . '" id="' . htmlspecialchars($field['name']) . '_' . htmlspecialchars($option['value']) . '" value="' . htmlspecialchars($option['value']) . '" class="form-check-input"' . ($value === $option['value'] ? ' checked' : '') . ($field['required'] ? ' required' : '') . '>';
			$html .= '<label for="' . htmlspecialchars($field['name']) . '_' . htmlspecialchars($option['value']) . '" class="form-check-label">' . htmlspecialchars($option['label']) . '</label>';
			$html .= '</div>';
		}
		return $html;
	}

	private function generateCheckboxHtml(array $field, ?string $value): string
	{
		$html = '';
		if (isset($field['options'])) {
			foreach ($field['options'] as $option) {
				$html .= '<div class="form-check">';
				$html .= '<input type="checkbox" name="' . htmlspecialchars($field['name']) . '[]" id="' . htmlspecialchars($field['name']) . '_' . htmlspecialchars($option['value']) . '" value="' . htmlspecialchars($option['value']) . '" class="form-check-input"' . ($value === $option['value'] ? ' checked' : '') . '>';
				$html .= '<label for="' . htmlspecialchars($field['name']) . '_' . htmlspecialchars($option['value']) . '" class="form-check-label">' . htmlspecialchars($option['label']) . '</label>';
				$html .= '</div>';
			}
		} else {
			$html .= '<input type="checkbox" name="' . htmlspecialchars($field['name']) . '" value="1" class="form-check-input"' . ($value ? ' checked' : '') . '>';
			$html .= '<label class="form-check-label">' . htmlspecialchars($field['label']) . '</label>';
		}
		return $html;
	}

}
