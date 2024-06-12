<?php

/**
 * Configure Admin Dashboard Settings UI, logic and assets.
 *
 * @package PCC
 */

namespace PCC;

use function add_action;
use function filemtime;
use function wp_enqueue_script;

use const PCC_HANDLE;
use const PCC_PLUGIN_DIR;
use const PCC_PLUGIN_DIR_URL;

/**
 * Class Settings
 *
 * @package PCC
 */
class Settings
{
	private $pages = [
		'authenticate' => PCC_PLUGIN_DIR . 'admin/templates/partials/authenticate.php',
		'connected-collection'  => PCC_PLUGIN_DIR . 'admin/templates/partials/connected-collection.php',
		'create-collection' => PCC_PLUGIN_DIR . 'admin/templates/partials/create-collection.php',
		'disconnect-confirmation'  => PCC_PLUGIN_DIR . 'admin/templates/partials/disconnect-confirmation.php',
		'setup' => PCC_PLUGIN_DIR . 'admin/templates/partials/setup.php',
	];

	public function __construct()
	{
		$this->addHooks();
	}

	/**
	 * Add required hooks.
	 *
	 * @return void
	 */
	private function addHooks(): void
	{
		add_action('admin_menu', [$this, 'addMenu']);
		add_action(
			'admin_enqueue_scripts',
			[$this, 'enqueueAssets']
		);
		add_action('admin_menu', [$this, 'pluginAdminNotice']);
	}

	/**
	 * Register settings page.
	 *
	 * @return void
	 */
	public function addMenu(): void
	{
		add_menu_page(
			esc_html__('PCC', PCC_HANDLE),
			esc_html__('PCC', PCC_HANDLE),
			'manage_options',
			PCC_HANDLE,
			[$this, 'renderSettingsPage'],
			'dashicons-format-aside',
			20
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function renderSettingsPage(): void
	{
		$view = isset($_GET['view']) ? $_GET['view'] : null;
		if ($view && isset($this->pages[$view])) {
			require $this->pages[$view];
			return;
		}

		// Site id is set and Credentials are set
		if ($this->getSiteId() && $this->getCredentials()) {
			require $this->pages['connected-collection'];
		// Credentials is set but Site id is not set then user needs to create a new site
		} elseif ($this->getCredentials()) {
			require $this->pages['create-collection'];
		} else {
			require $this->pages['setup'];
		}
	}

	/**
	 * Enqueue plugin assets on the WP Admin Dashboard.
	 *
	 * @return void
	 */
	public function enqueueAssets(): void
	{
		wp_enqueue_script(
			PCC_HANDLE,
			PCC_PLUGIN_DIR_URL . 'dist/app.js',
			[],
			filemtime(PCC_PLUGIN_DIR . 'dist/app.js'),
			true
		);

		wp_enqueue_style(
			PCC_HANDLE,
			PCC_PLUGIN_DIR_URL . 'dist/app.css',
			[],
			filemtime(PCC_PLUGIN_DIR . 'dist/app.css')
		);

		wp_localize_script(
			PCC_HANDLE,
			'PCCAdmin',
			[
				'rest_url' => get_rest_url(get_current_blog_id(), PCC_API_NAMESPACE),
				'nonce' => wp_create_nonce('wp_rest'),
				'plugin_main_page' => menu_page_url(PCC_HANDLE, false),
				'site_url' => site_url(),
			] + ['credentials' => $this->getCredentials()]
		);
	}

	/**
	 * Get credentials from the database.
	 *
	 * @return array|mixed
	 */
	private function getCredentials()
	{
		$pccCredentials = get_option(PCC_CREDENTIALS_OPTION_KEY);

		return $pccCredentials ? unserialize($pccCredentials) : [];
	}

	/**
	 * @return false|mixed|null
	 */
	private function getSiteId()
	{
		return get_option(PCC_SITE_ID_OPTION_KEY);
	}

	/**
	 * Show notification when authentication details are not set or collection not created
	 */
	public function pluginAdminNotice()
	{
		global $pagenow;
		if ($pagenow != 'plugins.php') {
			return;
		}

		// Show notification when authentication details are not set or collection not created
		if (!$this->getCredentials() || !$this->getSiteId()) {
			add_action('admin_notices', [$this, 'pluginNotification']);
		}
	}

	/**
	 * Plugin notification to continue setup
	 */
	public function pluginNotification()
	{
		require PCC_PLUGIN_DIR . 'admin/templates/partials/plugin-notification.php';
	}
}
