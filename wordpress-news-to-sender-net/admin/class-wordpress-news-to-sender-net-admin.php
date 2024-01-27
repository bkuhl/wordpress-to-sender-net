<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin
 * @author     Your Name <email@example.com>
 */
class Plugin_Name_Admin {

	const SETTINGS_GROUP = 'wordpress-news-to-sender-net_settings_group';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		require_once plugin_dir_path(__DIR__).'includes/class-wordpress-news-to-sender-net-sender-api-lib.php';
	}

    public function initializeAdmin() {
		register_setting(self::SETTINGS_GROUP, 'api_token', [
			'type' => 'string',
			'sanitize_callback' => function ($newValue) {
				$newValue = sanitize_text_field($newValue);
				if (substr($newValue, -5) !== '*****') {
					return Plugin_Name_Sender_Net_Lib::encryptApiToken($newValue);
				}

				return get_option('api_token');
			},
			'show_in_rest' => false,
		]);

		register_setting(self::SETTINGS_GROUP, 'selected_groups', [
			'type' => 'array',
		]);

		register_setting(self::SETTINGS_GROUP, 'autopublish', [
			'type' => 'boolean',
			'default' => false,
		]);
    }

    public function displayAdminSettings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $existing_api_token = Plugin_Name_Sender_Net_Lib::apiToken();
		$selected_groups = get_option('selected_groups', []);
		$autopublish = get_option('autopublish', false);

        ?>
        <div class="wrap">
            <h2>News to Sender.net Settings</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::SETTINGS_GROUP);
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">API Token</th>
                        <td>
                            <?php
                            if ($existing_api_token) {
                                $masked_api_token = substr($existing_api_token, 0, 5) . str_repeat('*', strlen($existing_api_token) - 5);
                                echo '<input type="text" id="api_token" name="api_token" value="' . esc_attr($masked_api_token) . '" class="regular-text" />';
                            } else {
                                echo '<input type="text" id="api_token" name="api_token" value="" class="regular-text" />';
                            }
                            ?>
                            <p class="description">
                                You can obtain a new API token from <a href="https://app.sender.net/settings/tokens" target="_blank">https://app.sender.net/settings/tokens</a>.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php

				if (!empty($existing_api_token)) {

					$senderNetApi = new Plugin_Name_Sender_Net_Lib();
					try {
						$groups = $senderNetApi->getGroups($existing_api_token);
					} catch (RuntimeException $e) {
						?> <p class="error-message"><?php echo esc_html($e->getMessage()); ?></p> <?php
						submit_button();
						return;
					}

					?>

					<table class="form-table">
						<tr>
							<th scope="row">Autopublish</th>
							<td>
								<label>
									<input type="checkbox" name="autopublish" value="1" <?php checked(1, $autopublish); ?>>
									<strong>Enabled</strong>
								</label>
								<p class="description">
									When checked, the Campaign that is created in Sender.net will also be automatically published (e.g. no chance to review/edit it manually).
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Groups</th>
							<td>
								<?php
								if (!empty($groups)) {
									foreach ($groups as $group) {
										$checked = in_array($group['id'], $selected_groups) ? 'checked' : ''; // Check if the group ID is in the selected groups
										echo '<label><input type="checkbox" name="selected_groups[]" value="' . esc_attr($group['id']) . '" ' . $checked . '> <strong>' . esc_html($group['name']) . '</strong></label><br>';
									}
								} else {
									echo '<p>No groups available.</p>';
								}
								?>
							</td>
						</tr>
					</table>

					<?php
				}
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function addSettingsMenuNavigation() {
        add_options_page(
            'News To Sender.net Settings',        // Page title
            'News To Sender.net',        // Menu title
            'manage_options',              // Capability
			self::SETTINGS_GROUP,        // Menu slug
            [$this, 'displayAdminSettings'] // Callback function to display the settings page
        );
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wordpress-news-to-sender-net-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wordpress-news-to-sender-net-admin.js', array( 'jquery' ), $this->version, false );

	}

}
