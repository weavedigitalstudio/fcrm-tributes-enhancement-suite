<?php
/**
 * The backup functionality of the plugin.
 *
 * @link       https://weave.co.nz
 * @since      1.4.0
 *
 * @package    FCRM_Enhancement_Suite
 * @subpackage FCRM_Enhancement_Suite/includes
 */

namespace FCRM\EnhancementSuite;

/**
 * The backup functionality of the plugin.
 *
 * Handles exporting and importing of FireHawk Tributes Plugin settings
 *
 * @package    FCRM_Enhancement_Suite
 * @subpackage FCRM_Enhancement_Suite/includes
 * @author     Weave Digital Studio <support@weave.co.nz>
 */
class Backup extends Enhancement_Base
{
	/**
	 * Settings list with descriptions
	 *
	 * @var array
	 */
	private $settings_list = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.4.0
	 */
	public function __construct()
	{
		parent::__construct("backup", "Backup & Restore");
	}

	/**
	 * Initialize the module
	 *
	 * Required implementation of abstract method from Enhancement_Base
	 */
	protected function init(): void
	{
		$this->init_settings_list();
		$this->setup_ajax_handlers();
	}

	/**
	 * Initialize settings list
	 *
	 * @since    1.4.0
	 */
	private function init_settings_list(): void
	{
		$this->settings_list = [
			"fcrm_tributes_auth_token" =>
				"FireHawk Tributes Token (The main API Token)",
			"fcrm_tributes_team_brand" => "Brand ID",
			"fcrm_tributes_default_image" => "Default Image URL",
			"fcrm_tributes_date_format" => "Date Format",
			"fcrm_tributes_dob_format" => "DOB Format",
			"fcrm_tributes_hide_dob" => "Hide DOB",
			"fcrm_tributes_event_date_format" => "Event Date Format",
			"fcrm_tributes_event_end_date_format" => "Event End Date Format",
			"fcrm_tributes_date_locale" => "Date Locale",
			"fcrm_tributes_meta_title" => "Meta Title",
			"fcrm_tributes_meta_description" => "Meta Description",
			"fcrm_tributes_single_page_id" => "Single Page ID",
			"fcrm_tributes_search_page_id" => "Search Page ID",
			"fcrm_tributes_readable_permalinks" => "Readable Permalinks",
			"fcrm_tributes_show_location" => "Show Location",
			"fcrm_tributes_redirect_query_parameter" => "Redirect Query Parameter",
			"fcrm_tributes_contribute_page" => "Contribute Page",
			"fcrm_tributes_page_options_printing_button" => "Printing Button Label",
		];
	}

	/**
	 * Setup AJAX handlers
	 *
	 * @since    1.4.0
	 */
	private function setup_ajax_handlers(): void
	{
		add_action("wp_ajax_fcrm_export_settings", [$this, "handle_export"]);
		add_action("wp_ajax_fcrm_import_settings", [$this, "handle_import"]);
		add_action("wp_ajax_fcrm_reset_settings", [$this, "handle_reset"]);
	}

	/**
	 * Register settings
	 *
	 * @since    1.4.0
	 */
	public function register_settings(): void
	{
		// No settings to register for this module
	}

	/**
	 * Enqueue admin-specific scripts
	 *
	 * @since    1.4.0
	 */
	public function enqueue_admin_assets($hook): void
	{
		if ("toplevel_page_fcrm-enhancements" !== $hook) {
			return;
		}

		wp_enqueue_script("jquery");

		wp_enqueue_script(
			"fcrm-backup-script",
			FCRM_ENHANCEMENT_URL . "assets/js/admin/backup.js",
			["jquery"],
			FCRM_ENHANCEMENT_VERSION,
			true
		);

		wp_localize_script("fcrm-backup-script", "fcrmBackupData", [
			"nonce" => wp_create_nonce("fcrm_backup_nonce"),
			"ajaxurl" => admin_url("admin-ajax.php"),
		]);

		wp_enqueue_style(
			"fcrm-backup-styles",
			FCRM_ENHANCEMENT_URL . "assets/css/admin/backup.css",
			[],
			FCRM_ENHANCEMENT_VERSION
		);
	}

	/**
	 * Handle export settings request
	 *
	 * @since    1.4.0
	 */
	public function handle_export(): void
	{
		try {
			// Check nonce
			check_ajax_referer("fcrm_backup_nonce", "nonce");

			// Check permissions
			if (!current_user_can("manage_options")) {
				wp_send_json_error([
					"message" => "Permission denied",
				]);
				return;
			}

			// Get and validate settings
			if (!isset($_POST["settings"])) {
				wp_send_json_error([
					"message" => "No settings provided",
				]);
				return;
			}

			$selected_settings = json_decode(stripslashes($_POST["settings"]), true);

			if (!is_array($selected_settings)) {
				wp_send_json_error([
					"message" => "Invalid settings format",
				]);
				return;
			}

			// Export the data
			$export_data = [];
			foreach ($selected_settings as $setting) {
				if (array_key_exists($setting, $this->settings_list)) {
					$export_data[$setting] = get_option($setting);
				}
			}

			// Add metadata
			$export_data["_meta"] = [
				"exported_from" => get_bloginfo("name"),
				"exported_url" => get_bloginfo("url"),
				"exported_date" => current_time("mysql"),
				"plugin_version" => FCRM_ENHANCEMENT_VERSION,
			];

			wp_send_json_success($export_data);
		} catch (Exception $e) {
			error_log("FCRM Export Error: " . $e->getMessage());
			wp_send_json_error([
				"message" => "Export failed: " . $e->getMessage(),
			]);
		}
	}

	/**
	 * Handle import settings request
	 *
	 * @since    1.4.0
	 */
	public function handle_import(): void
	{
		try {
			// Check nonce
			check_ajax_referer("fcrm_backup_nonce", "nonce");

			// Check permissions
			if (!current_user_can("manage_options")) {
				wp_send_json_error([
					"message" => "Permission denied",
				]);
				return;
			}

			// Validate input
			if (!isset($_POST["settings"]) || !isset($_POST["selected"])) {
				wp_send_json_error([
					"message" => "Invalid request data",
				]);
				return;
			}

			$settings = json_decode(stripslashes($_POST["settings"]), true);
			$selected = json_decode(stripslashes($_POST["selected"]), true);

			if (!is_array($settings) || !is_array($selected)) {
				wp_send_json_error([
					"message" => "Invalid data format",
				]);
				return;
			}

			// Import selected settings
			foreach ($selected as $setting_key) {
				if (
					isset($settings[$setting_key]) &&
					array_key_exists($setting_key, $this->settings_list)
				) {
					update_option($setting_key, $settings[$setting_key]);
				}
			}

			wp_send_json_success([
				"message" => "Settings imported successfully",
			]);
		} catch (Exception $e) {
			error_log("FCRM Import Error: " . $e->getMessage());
			wp_send_json_error([
				"message" => "Import failed: " . $e->getMessage(),
			]);
		}
	}

	/**
	 * Handle reset settings request
	 *
	 * @since    1.4.0
	 */
	public function handle_reset(): void
	{
		try {
			// Check nonce
			check_ajax_referer("fcrm_backup_nonce", "nonce");

			// Check permissions
			if (!current_user_can("manage_options")) {
				wp_send_json_error([
					"message" => "Permission denied",
				]);
				return;
			}

			// Validate settings
			if (!isset($_POST["settings"])) {
				wp_send_json_error([
					"message" => "No settings provided",
				]);
				return;
			}

			$settings = json_decode(stripslashes($_POST["settings"]), true);
			if (!is_array($settings)) {
				wp_send_json_error([
					"message" => "Invalid settings format",
				]);
				return;
			}

			// Reset selected settings
			foreach ($settings as $setting) {
				if (array_key_exists($setting, $this->settings_list)) {
					delete_option($setting);
				}
			}

			wp_send_json_success([
				"message" => "Settings reset successfully",
			]);
		} catch (Exception $e) {
			error_log("FCRM Reset Error: " . $e->getMessage());
			wp_send_json_error([
				"message" => "Reset failed: " . $e->getMessage(),
			]);
		}
	}

	/**
	 * Render the settings page
	 *
	 * @since    1.4.0
	 */
	public function render_settings(): void
	{
		// Security check
		if (!current_user_can("manage_options")) {
			return;
		} ?>
		<div id="fcrm-backup-container" class="wrap">
			<div class="notice-container"></div>

			<div class="fcrm-backup-description">
				<p><?php _e(
    	"Use this tool to backup and restore your FireHawk Tributes Plugin settings. You can select specific settings to export or import, making it easy to transfer configurations between different websites.",
    	"fcrm-enhancement-suite"
    ); ?></p>
				<p><?php _e(
    	"The backup file includes metadata about when and where the backup was created, making it easier to manage multiple configurations.",
    	"fcrm-enhancement-suite"
    ); ?></p>
				<p><?php _e(
    	"This tool was primarily developed for internal use at Weave Digital Studio & Human Kind and with the funeral websites we build. We cannot guarantee compatibility with all WordPress setups or themes",
    	"fcrm-enhancement-suite"
    ); ?></p>
				<p class="description"><?php _e(
    	'Note: While this tool only affects plugin settings, it\'s always good practice to maintain regular backups of your entire WordPress site.',
    	"fcrm-enhancement-suite"
    ); ?></p>
			</div>

			<div class="fcrm-backup-content">
				<div class="fcrm-backup-settings">
					<div class="fcrm-backup-header">
						<label class="select-all">
							<input type="checkbox" id="select-all-settings">
							<span><?php _e("Select All Settings", "fcrm-enhancement-suite"); ?></span>
						</label>
					</div>

					<div class="settings-list">
						<?php foreach ($this->settings_list as $key => $label): ?>
							<div class="setting-item">
								<label>
									<input type="checkbox" class="setting-checkbox" value="<?php echo esc_attr(
         	$key
         ); ?>">
									<span class="setting-label"><?php echo esc_html($label); ?></span>
									<code class="setting-key"><?php echo esc_html($key); ?></code>
								</label>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="fcrm-backup-actions">
					<div class="action-buttons">
						<button id="export-settings" class="button button-primary" disabled>
							<span class="dashicons dashicons-download"></span>
							<?php _e("Export Selected", "fcrm-enhancement-suite"); ?>
						</button>

						<button id="reset-settings" class="button button-link-delete" disabled>
							<span class="dashicons dashicons-image-rotate"></span>
							<?php _e("Reset Selected", "fcrm-enhancement-suite"); ?>
						</button>
					</div>

					<div class="import-section">
						<input type="file" id="import-file" accept=".json" class="import-file">
						<button id="import-settings" class="button button-secondary" disabled>
							<span class="dashicons dashicons-upload"></span>
							<?php _e("Import Selected", "fcrm-enhancement-suite"); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
