<?php

	namespace Salad\Core;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFunction;
	use Twig\TwigFilter;

	use Salad\Core\Application;
	use Salad\Core\Database;
	use Salad\Core\BaseComponent;
	use App\Models\User;
	use App\Models\Page;
	use App\Models\Menu;

	class TwigExtension extends AbstractExtension
	{

		private $app;
		private $db;
		protected $user;
		private $page;
		private $menu;

		public function __construct()
		{
			$this->app = Application::$app;
			$this->db = new Database();
			$this->user = new User;
			$this->page = new Page;
			$this->menu = new Menu;
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
				new TwigFunction('get_site_menu', [$this, 'getSiteMenu']),
			];
		}

		public function getFilters(): array
		{
			return [
				new TwigFilter('uppercase', [$this, 'toUppercase']),
				new TwigFilter('base_url', [$this, 'baseUrl']),
				new TwigFilter('css', [$this, 'renderCSS']),
				new TwigFilter('remove_img', [$this, 'removeImgTags']),
				new TwigFilter('slugify', [$this, 'slugify']),
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
			$userId = $this->app->session->get('user_id');
			$stmt = $this->user->findById($userId);
			return $stmt['email'];
		}

		public function getUpdateForm($table, $id)
		{
			return $this->app->extension->getUpdateForm($table, false, $id);
		}

		public function getRequestURI()
		{
			return $_SERVER['REQUEST_URI'];
		}

		public function getExtensions($type): array
		{
			return $this->app->extension->getExtensions()[$type];
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
			return $this->app->getBaseUrl();
		}

		public function toUppercase(string $text): string
		{
			return strtoupper($text);
		}

		public function baseUrl(string $url): string
		{
			return $this->app->getBaseUrl() . $url;
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

		public function removeImgTags($content)
		{
			return preg_replace('/<img[^>]*>/i', '', $content);
		}

		
		public function slugify($text)
		{
			$text = strtolower($text);
			$text = preg_replace('/\s+/', '-', $text);
			
			$text = preg_replace('/[^a-z0-9\-]/', '', $text);
			$text = preg_replace('/-+/', '-', $text);
			$text = trim($text, '-');
			
			return $text;
		}
		
		public function getSiteMenu()
		{
			$menus = $this->menu->fetchAll();
			$data = [];
			foreach ($menus as $key => $value) {
				$sub_menus = $this->menu->fetchSubMenu($value['id']);
				$page_url = null;
				if($value['page_id']){
					$page_url = $this->page->findById($value['page_id'])['slug'];
				}

				$data[] = [
				"index" => $key,
				"menu"  => $value['menu'],
				"menu_url"  => $sub_menus ? null : $page_url,
				"submenu"   => $this->getSubMenu($value['id'])
				];
			}

			return $data;
		}
		
		public function getSubMenu($parent_id)
		{
			$menus = $this->menu->fetchSubMenu($parent_id);
			$data = [];
			foreach ($menus as $key => $value) {
				$sub_menus = $this->menu->fetchSubMenu($value['id']);
				$page_url = null;
				if($value['page_id']){
					$page_url = $this->page->findById($value['page_id'])['slug'];
				}

				$data[] = [
				"index" => $key,
				"menu"  => $value['menu'],
				"menu_url"  => $sub_menus ? null : $page_url,
				// "submenu"   => $sub_menus
				];
			}

			return $data;
		}

	}
