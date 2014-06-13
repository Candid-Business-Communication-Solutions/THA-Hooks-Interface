<?php
/**
 * Plugin Name.
 *
 * @package   THA_Hooks_Interface_Admin
 * @author    ThematoSoup <contact@thematosoup.com>
 * @license   GPL-2.0+
 * @link      http://thematosoup.com
 * @copyright 2013 ThematoSoup
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * TODO: Rename this class to a proper name for your plugin.
 *
 * @package THA_Hooks_Interface_Admin
 * @author  ThematoSoup <contact@thematosoup.com>
 */
class THA_Hooks_Interface_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		$plugin = THA_Hooks_Interface::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_action( 'admin_init', array( $this, 'plugin_options' ) );
	}

	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == viewsself::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'THA Hooks Interface', $this->plugin_slug ),
			__( 'THA Hooks', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( '/admin.php' );
	}



	public function plugin_options() {

		$all_tha_hooks = tha_interface_all_hooks();
		// Register a settings section for each hooks group
		foreach ( $all_tha_hooks as $tha_hooks_group => $tha_hooks_group_values ) :
		
			// Check if theme declares support for this section
			if ( current_theme_supports( 'tha_hooks', $tha_hooks_group ) || 'WordPress' == $tha_hooks_group ) :
			
				// First, we register a section. This is necessary since all future options must belong to one.  
				add_settings_section(  
					'tha_hooks_interface_section_' . $tha_hooks_group,
					'',
					'',
					'tha_hooks_interface_' . $tha_hooks_group
				); 
		
				// For each hook in hooks group, add settings field
				foreach ( $tha_hooks_group_values['hooks'] as $hook_name => $hook_description ) :
	
					// Next, we will introduce the fields for toggling the visibility of content elements.
					add_settings_field(	
						$hook_name,
						$hook_name,
						array( $this, 'field_cb' ),
						'tha_hooks_interface_' . $tha_hooks_group,
						'tha_hooks_interface_section_' . $tha_hooks_group,
						array(
							$tha_hooks_group,
							$hook_name,
							$hook_description
						)
					);
	
				endforeach;
		
				// Finally, we register the fields with WordPress  
				register_setting(  
				    'tha_hooks_interface_' . $tha_hooks_group,  
				    'tha_hooks_interface_' . $tha_hooks_group,
				    array( $this, 'sanitize_field' )
				); 
			
			endif; // Theme support check		
		
		endforeach;
			    
	}
	


	public function field_cb( $args ) {
		$hooks_group = $args[0];
		$hook_name = $args[1];
		$hook_description = $args[2];
		
		$output_field_name = 'tha_hooks_interface_' . $hooks_group . '[' . $hook_name . '][output]';
		$php_field_name = 'tha_hooks_interface_' . $hooks_group . '[' . $hook_name . '][php]';
		$shortcode_field_name = 'tha_hooks_interface_' . $hooks_group . '[' . $hook_name . '][shortcode]';
		$tha_interface_settings = get_option( 'tha_hooks_interface_' . $hooks_group );
		?>
		
		<p><?php echo $hook_description; ?></p>
		<p>
		<textarea style="font-family:monospace" rows="10" class="widefat" name="<?php echo $output_field_name; ?>" id="<?php echo $output_field_name; ?>"><?php echo htmlentities( $tha_interface_settings[ $hook_name ]['output'], ENT_QUOTES, 'UTF-8' ); ?></textarea>
		</p>
		
		<?php if ( current_user_can( 'unfiltered_html' ) ) : ?>
		<p>
		<label for="<?php echo $php_field_name; ?>">
			<input type="checkbox" name="<?php echo $php_field_name; ?>" id="<?php echo $php_field_name; ?>" value="1" <?php checked( $tha_interface_settings[ $hook_name ]['php'], 1 ); ?> />
			<?php _e( 'Execute PHP in this hook (must be enclodes in opening and closing PHP tags)', $this->plugin_slug ); ?>
		</label>
		</p>
		<?php endif; ?>

		<p>
		<label for="<?php echo $shortcode_field_name; ?>">
			<input type="checkbox" name="<?php echo $shortcode_field_name; ?>" id="<?php echo $shortcode_field_name; ?>" value="1" <?php checked( $tha_interface_settings[ $hook_name ]['shortcode'], 1 ); ?> />
			<?php _e( 'Run shortcodes in this hook', $this->plugin_slug ); ?>
		</label>
		</p>
	<?php }
	
	
	/**
	 * Sanitize the field, filter out HTML if a user can't post HTML markup.
	 *
	 * @since    1.0.0
	 */
	public function sanitize_field( $field ) {
		
		if ( ( current_user_can( 'unfiltered_html' ) ) ) :
			return $field;
		else :
			return stripslashes( wp_filter_post_kses( $field ) );
		endif;
		
	}

}
