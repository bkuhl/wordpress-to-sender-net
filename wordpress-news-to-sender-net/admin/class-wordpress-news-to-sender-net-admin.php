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
	}

    public function initializeAdmin() {
		register_setting(self::SETTINGS_GROUP, Plugin_Name::OPTION_API_TOKEN, [
			'type' => 'string',
			'sanitize_callback' => function ($newValue) {
				$newValue = sanitize_text_field($newValue);
				if (substr($newValue, -5) !== '*****') {
					return Plugin_Name_Sender_Net_Lib::encryptApiToken($newValue);
				}

				return get_option(Plugin_Name::OPTION_API_TOKEN);
			},
			'show_in_rest' => false,
		]);

		register_setting(self::SETTINGS_GROUP, Plugin_Name::OPTION_AUTOPUBLISH, [
			'type' => 'boolean',
			'default' => false,
		]);

		register_setting(self::SETTINGS_GROUP, Plugin_Name::OPTION_SELECTED_GROUPS, [
			'type' => 'array',
		]);

		register_setting(self::SETTINGS_GROUP, Plugin_Name::OPTION_REPLY_TO, [
			'type' => 'string',
		]);
    }

    public function displayAdminSettings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $existing_api_token = Plugin_Name_Sender_Net_Lib::apiToken();
		$selected_groups = get_option(Plugin_Name::OPTION_SELECTED_GROUPS, []);
		$autopublish = get_option(Plugin_Name::OPTION_AUTOPUBLISH, false);
		$replyTo = get_option(Plugin_Name::OPTION_REPLY_TO);

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
                                echo '<input type="text" id="api_token" name="'.Plugin_Name::OPTION_API_TOKEN.'" value="' . esc_attr($masked_api_token) . '" class="regular-text" />';
                            } else {
                                echo '<input type="text" id="api_token" name="'.Plugin_Name::OPTION_API_TOKEN.'" value="" class="regular-text" />';
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
									<input type="checkbox" name="<?=Plugin_Name::OPTION_AUTOPUBLISH?>" value="1" <?php checked(1, $autopublish); ?>>
									<strong>Enabled</strong>
								</label>
								<p class="description">
									When checked, the Campaign that is created in Sender.net will also be automatically published (e.g. no chance to review/edit it manually).
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Reply to address</th>
							<td>
								<label>
									<input type="email" name="<?=Plugin_Name::OPTION_REPLY_TO?>" value="<?= esc_attr($replyTo) ?>">
								</label>
								<p class="description">
									The from/reply-to address to use for the campaign.
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
										echo '<label><input type="checkbox" name="'.Plugin_Name::OPTION_SELECTED_GROUPS.'[]" value="' . esc_attr($group['id']) . '" ' . $checked . '> <strong>' . esc_html($group['name']) . '</strong></label><br>';
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

	public function createCampaign($postId) {
		if (wp_is_post_revision($postId)) {
			return;
		}

		$post_type = get_post_type($postId);
		if ($post_type !== 'post') {
			return;
		}

		$post = get_post($postId);
		$senderNetApi = new Plugin_Name_Sender_Net_Lib();
		$campaignId = $senderNetApi->createCampaign(
			$post->post_title,
			$post->post_content
		);

		if (get_option(Plugin_Name::OPTION_AUTOPUBLISH)) {
			$senderNetApi->sendCampaign($campaignId);
		}
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
