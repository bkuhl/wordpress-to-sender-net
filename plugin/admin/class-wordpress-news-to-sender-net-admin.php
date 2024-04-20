<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    WordpressToSender
 * @subpackage WordpressToSender/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WordpressToSender
 * @subpackage WordpressToSender/admin
 * @author     Your Name <email@example.com>
 */
class WordpressToSender_Admin {

	const SETTINGS_GROUP = 'wordpress-news-to-sender-net_settings_group';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $WordpressToSender    The ID of this plugin.
	 */
	private $WordpressToSender;

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
	 * @param      string    $WordpressToSender       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $WordpressToSender, $version ) {

		$this->WordpressToSender = $WordpressToSender;
		$this->version = $version;
	}

    public function initializeAdmin() {
		register_setting(self::SETTINGS_GROUP, WordpressToSender::OPTION_API_TOKEN, [
			'type' => 'string',
			'sanitize_callback' => function ($newValue) {
				$newValue = sanitize_text_field($newValue);
				if (substr($newValue, -5) !== '*****') {
					return WordpressToSender_Sender_Net_Lib::encryptApiToken($newValue);
				}

				return get_option(WordpressToSender::OPTION_API_TOKEN);
			},
			'show_in_rest' => false,
		]);

		register_setting(self::SETTINGS_GROUP, WordpressToSender::OPTION_POST_TYPE, [
			'type' => 'string',
			'default' => 'post',
		]);

		register_setting(self::SETTINGS_GROUP, WordpressToSender::OPTION_AUTOPUBLISH, [
			'type' => 'boolean',
			'default' => false,
		]);

		register_setting(self::SETTINGS_GROUP, WordpressToSender::OPTION_SELECTED_GROUPS, [
			'type' => 'array',
		]);

		register_setting(self::SETTINGS_GROUP, WordpressToSender::OPTION_REPLY_TO, [
			'type' => 'string',
		]);

		register_setting(self::SETTINGS_GROUP, WordpressToSender::OPTION_MAIL_TEMPLATE, [
			'type' => 'string',
		]);
    }

    public function displayAdminSettings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $existing_api_token = WordpressToSender_Sender_Net_Lib::apiToken();
		$selected_groups = get_option(WordpressToSender::OPTION_SELECTED_GROUPS, []);
		$autopublish = get_option(WordpressToSender::OPTION_AUTOPUBLISH, false);
		$replyTo = get_option(WordpressToSender::OPTION_REPLY_TO);
		$template = get_option(WordpressToSender::OPTION_MAIL_TEMPLATE);

		$post_types = get_post_types(['public' => true], 'objects');
		$selected_post_type = get_option(WordpressToSender::OPTION_POST_TYPE);

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
                                echo '<input type="text" id="api_token" name="'.esc_attr(WordpressToSender::OPTION_API_TOKEN).'" value="' . esc_attr($masked_api_token) . '" class="regular-text" />';
                            } else {
                                echo '<input type="text" id="api_token" name="'.esc_attr(WordpressToSender::OPTION_API_TOKEN).'" value="" class="regular-text" />';
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

					$senderNetApi = new WordpressToSender_Sender_Net_Lib();
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
							<th scope="row">Create Campaigns For</th>
							<td>
								<select name="<?php echo esc_attr(WordpressToSender::OPTION_POST_TYPE); ?>">
									<?php foreach ($post_types as $post_type => $post_type_obj) : ?>
										<option value="<?php echo esc_attr($post_type); ?>" <?php selected($selected_post_type, $post_type); ?>>
											<?php echo esc_html($post_type_obj->label); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">When a new post of this type is created, we'll create an email campaign for it automatically.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Autopublish</th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr(WordpressToSender::OPTION_AUTOPUBLISH); ?>" value="1" <?php checked(1, $autopublish); ?>>
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
									<input type="email" name="<?php echo esc_attr(WordpressToSender::OPTION_REPLY_TO); ?>" value="<?php echo esc_attr($replyTo); ?>">
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
										echo '<label><input type="checkbox" name="'.esc_attr(WordpressToSender::OPTION_SELECTED_GROUPS).'[]" value="' . esc_attr($group['id']) . '" ' . esc_attr($checked) . '> <strong>' . esc_html($group['name']) . '</strong></label><br>';
									}
								} else {
									echo '<p>No groups available.</p>';
								}
								?>
							</td>
						</tr>
						<tr>
							<th scope="row">Email Template</th>
							<td>
								<textarea id="email-template" name="<?php echo esc_attr(WordpressToSender::OPTION_MAIL_TEMPLATE); ?>" rows="15" style="width: 100%;"><?php echo esc_textarea($template); ?></textarea>
								<p class="description">
									The HTML template must contain <strong>{{BODY_HERE}}</strong> which will be replaced with the contents of your Post.  <strong>{{TITLE_HERE}}</strong> will also be replaced with the title.  You'll also have to <a href="https://help.sender.net/knowledgebase/i-am-creating-a-html-campaign-but-i-cant-send-it-out-the-error-message-says-your-email-does-not-contain-an-unsubscribe-link/" target="_blank">include an unsubscribe link</a> in the footer.
								</p>
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

	public function createCampaignOnPublish($postId)
	{
		$post = get_post($postId);

		if ($post->post_type === 'post' && $post->post_status === 'publish' && $post->post_date === $post->post_modified) {
			$template = get_option(WordpressToSender::OPTION_MAIL_TEMPLATE);

			$replacements = [
				'{{TITLE_HERE}}' => $post->post_title,
				'{{BODY_HERE}}' => $post->post_content,
			];

			$senderNetApi = new WordpressToSender_Sender_Net_Lib();
			$campaignId = $senderNetApi->createCampaign(
				$post->post_title,
				str_replace(array_keys($replacements), array_values($replacements), $template)
			);

			if (get_option(WordpressToSender::OPTION_AUTOPUBLISH)) {
				$senderNetApi->sendCampaign($campaignId);
			}
			exit;
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
		 * defined in WordpressToSender_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WordpressToSender_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->WordpressToSender, plugin_dir_url( __FILE__ ) . 'css/wordpress-news-to-sender-net-admin.css', array(), $this->version, 'all' );

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
		 * defined in WordpressToSender_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WordpressToSender_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->WordpressToSender, plugin_dir_url( __FILE__ ) . 'js/wordpress-news-to-sender-net-admin.js', array( 'jquery' ), $this->version, false );

	}

}
