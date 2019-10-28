<?php
/**
* The admin Truth class
*/
if ( ! class_exists( 'Truth_Admin' ) ) {

	/**
	* lets get started
	*/
	class Truth_Admin {

		/**
		* Static property to hold our singleton instance
		* @var $instance
		*/
		static $instance = false;

		/**
		* Static property to hold our singleton instance
		* @var $instance
		*/
		public $options = array();

		/**
		* this is our constructor.
		* there are many like it, but this one is mine
		*/
		private function __construct() {

			$this->options = get_option( 'truth_settings' );

			register_activation_hook( TRUTH_FILE, array( $this, 'initialize_plugin' ) );

			require_once( TRUTH_DIR . 'class-truth-notice.php' );

			// Load the administrative Stylesheets and JavaScript
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			if ( TRUTH_AUTH_ALL ) {

				update_option( 'truth_authorization', true );

			} else {

				add_action( 'admin_init', array( $this, 'check_authorization' ) );

			}

			add_action( 'admin_init', array( $this, 'register_settings' ) );

		}

		/**
		* If an instance exists, this returns it.  If not, it creates one and
		* returns it.
		*
		* @return $instance
		*/
		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		* Register the stylesheets.
		*
		* @since    0.0.1
		*/
		public function enqueue_styles() {

			wp_enqueue_style( 'truth', plugin_dir_url( __FILE__ ) . 'css/truth-admin.css', array(), TRUTH_VERSION, 'all' );

		}

		/**
		* Register the JavaScript for the dashboard.
		*
		* @since    0.0.1
		*/
		public function enqueue_scripts() {

			wp_enqueue_script( 'truth', plugin_dir_url( __FILE__ ) . 'js/truth-admin.js', array( 'jquery' ), TRUTH_VERSION, false );
			wp_localize_script( 'truth', 'TRUTH', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

		}

		public function check_authorization() {

			if ( ! get_option( 'truth_authorization' ) ) {

				$args = array(
					'notice'  => 'truth-authorization',
					'class'   => 'update-nag',
					'message' => 'The <code>Truth</code> plugin needs authorization to link scripture references to an external site. Click <a href="#" id="authorize-truth" data-security="' . wp_create_nonce( 'authorize-truth' ) . '">here</a> to permit this action.',
					'echo'    => true,
				);

				$notice = new Truth_Notice( $args );

				$notice->show();

			}

		}

		public function register_settings() {

			register_setting( 'reading', 'truth_settings' );
			add_settings_field( 'truth_settings', 'Biblical References', array( $this, 'display_settings' ), 'reading', 'default' );

		}

		public function display_settings() {

			$sources = Truth::get_sources();

			foreach ( $sources as $source => $data ) {

				$versions[ $source ] = $data['versions'];

			}

			?>
			<fieldset id="truth-general-settings">
				<label>Bible Service: <select id="truth-engine" name="truth_settings[engine]"></label>
					<option value="biblesorg_highlighter" <?php selected( $this->options['engine'], 'biblesorg_highlighter' ); ?>>Bibles.org Highlighter</option>
					<option value="youversion" <?php selected( $this->options['engine'], 'youversion' ); ?>>YouVersion</option>
				</select></label>
				<span id="description-engine-biblesorg_highlighter" class="description" <?php echo 'biblesorg_highlighter' == $this->options['engine'] ? '' : 'style="display:none;"'; ?>>(allows display of verses via modal when verses are clicked)</span>
				<span id="description-engine-youversion" class="description" <?php echo 'youversion' == $this->options['engine'] ? '' : 'style="display:none;"'; ?>>(directs users to YouVersion.com upon click)</span>
			</p>
		</fieldset>

		<fieldset id="truth-biblesorg_highlighter-settings" <?php echo ( ! isset( $this->options['engine'] ) || 'biblesorg_highlighter' == $this->options['engine'] ) ? '' : 'style="display:none;"'; ?>>
			<h2>Bibles.org Highlighter Settings</h2>
			<label>Version: <select id="bible-version" name="truth_settings[biblesorg_highlighter][bible_version]">
				<?php foreach( $versions['biblesorg_highlighter'] as $languageGroup => $languageVersions ): ?>
					<optgroup label="<?php echo $languageGroup?>">
						<?php foreach( $languageVersions as $versionID => $versionInfo ): ?>
							<option value="<?php echo $versionID; ?>" <?php selected( $this->options['biblesorg_highlighter']['bible_version'], $versionID ); ?>><?php echo $versionInfo['name'] . ' (' . strtoupper( $versionInfo['abbr'] ). ')'; ?></option> <?php
						endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			</select></label><br />
			<label>Overwrite Highlighter Targeting: <input name="truth_settings[biblesorg_highlighter][target_ids]" value="<?php echo ! isset( $this->options['biblesorg_highlighter']['target_ids'] ) ? '' : $this->options['biblesorg_highlighter']['target_ids']; ?>" style="width: 40%"></label><span id="description-biblesorg-target-ids" class="description">(comma-separated list of DOM element ids, overrides default search for verse references)
			</fieldset>

			<fieldset id="truth-youversion-settings" <?php echo 'youversion' == $this->options['engine'] ? '' : 'style="display:none;"'; ?>>
				<h2>YouVersion Settings</h2>
				<label>Default Version: <select id="bible-version" name="truth_settings[youversion][bible_version]">
					<?php foreach( $versions['youversion'] as $languageGroup => $languageVersions ): ?>
						<optgroup label="<?php echo $languageGroup?>">
							<?php foreach( $languageVersions as $versionID => $versionInfo ): ?>
								<option value="<?php echo $versionID; ?>" <?php selected( $this->options['youversion']['bible_version'], $versionID ); ?>><?php echo $versionInfo['name'] . ' (' . strtoupper( $versionInfo['abbr'] ). ')'; ?></option> <?php
							endforeach; ?>
						</optgroup>
					<?php endforeach; ?>
				</select></label>
				<p><input type="checkbox" id="link_in_new_tab" name="truth_settings[link_in_new_tab]" value="1" <?php checked( $this->options['link_in_new_tab'], 1 ); ?>> <label for="link_in_new_tab">Open links in new tab.</label></input></p>
				<p><input type="checkbox" id="disable_auto_links" name="truth_settings[disable_auto_links]" value="1" <?php checked( $this->options['disable_auto_links'], 1 ); ?>> <label for="disable_auto_links">Disable auto-generation of links.</label></input> <span class="description">(maintains use of [truth] shortcode)</span></p>
				<p><label> Append Version to Shortcode Text: <select id="append_version" name="truth_settings[append_version]">
					<option value="none" <?php selected( $this->options['append_version'], 'none' ); ?>>No (Disabled)</option>
					<option value="abbr" <?php selected( $this->options['append_version'], 'abbr' ); ?>>Abbreviation</option>
					<option value="full" <?php selected( $this->options['append_version'], 'full' ); ?>>Full Name</option>
				</select></label>
			</p>
		</fieldset>

		<?php

	}

}

}


/**** DECLARE TYPEWHEEL NOTICES ****/
require_once( 'typewheel-notice/class-typewheel-notice.php' );

if ( apply_filters( 'truth_show_notices', true ) ) {
	add_action( 'admin_notices', 'typewheel_truth_notices' );
	/**
	* Displays a plugin notices
	*
	* @since    1.0
	*/
	function typewheel_truth_notices() {

		$prefix = str_replace( '-', '_', dirname( plugin_basename(__FILE__) ) );

		// Notice to show on plugin activation
		$activation_notice = array(

		);

		// Define the notices
		$typewheel_notices = array(
			$prefix . '-give' => array(
				'trigger' => true,
				'time' => time() + 2592000,
				'dismiss' => array( 'month' ),
				'type' => '',
				'content' => 'Is the <strong>Truth</strong> plugin working well for you? Please consider giving <a href="https://wordpress.org/support/plugin/truth/reviews/?rate=5#new-post" target="_blank"><i class="dashicons dashicons-star-filled"></i> a review</a>, <a href="https://twitter.com/intent/tweet/?url=https%3A%2F%2Fwordpress.org%2Fplugins%2Ftruth%2F" target="_blank"><i class="dashicons dashicons-twitter"></i> a tweet</a> or <a href="https://typewheel.xyz/give/?ref=Truth" target="_blank"><i class="dashicons dashicons-heart"></i> a donation</a> to encourage further development. Thanks! <a href="https://twitter.com/uamv/">@uamv</a>',
					'icon' => 'heart',
					'style' => array( 'background-image' => 'linear-gradient( to left, rgb(215, 215, 215), rgb(220, 213, 206) )', 'border-left-color' => '#3F3F3F' ),
					'location' => array( 'options-reading.php' ),
					'capability' => 'manage_options',
				),
			);

			// get the notice class
			new Typewheel_Notice( $prefix, $typewheel_notices, $activation_notice );

		} // end display_plugin_notices
	}

	/**
	* Deletes activation marker so it can be displayed when the plugin is reinstalled or reactivated
	*
	* @since    1.0
	*/
	function typewheel_truth_remove_activation_marker() {

		$prefix = str_replace( '-', '_', dirname( plugin_basename(__FILE__) ) );

		delete_option( $prefix . '_activated' );

	}
	register_deactivation_hook( dirname(__FILE__) . '/truth.php', 'typewheel_truth_remove_activation_marker' );
