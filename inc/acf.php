<?php
/**
 * ACF Customizations
 *
 * @package      RedBridge
 * @author       Red Bridge Internet
 * @since        1.0.0
 * @license      GPL-2.0+
 **/

class RB_ACF_Customizations {
	
	public function __construct() {
		
		// Only allow fields to be edited on development
		if ( ! defined( 'WP_LOCAL_DEV' ) || ! WP_LOCAL_DEV ) {
			add_filter( 'acf/settings/show_admin', '__return_false' );
		}
		
		// Save and sync fields.
		add_filter( 'acf/settings/save_json', array( $this, 'get_local_json_path' ) );
		add_filter( 'acf/settings/load_json', array( $this, 'add_local_json_path' ) );
		add_action( 'admin_init', array( $this, 'sync_fields_with_json' ) );
		
		// Register options page
		add_action( 'init', array( $this, 'register_options_page' ) );
		
		// Register Blocks
		add_action( 'acf/init', array( $this, 'register_blocks' ) );
		
		// Set Save Json Location for Child Theme
		add_filter('acf/settings/save_json', array( $this, 'child_theme_save_field_groups' ) );
	}
	
	/**
	 * Define where the local JSON is saved.
	 *
	 * @return string
	 */
	public function get_local_json_path() {
		return get_template_directory() . '/acf-json';
	}
	
	/**
	 * Add our path for the local JSON.
	 *
	 * @param array $paths
	 *
	 * @return array
	 */
	public function add_local_json_path( $paths ) {
		$paths[] = get_template_directory() . '/acf-json';
		
		return $paths;
	}
	
	/**
	 * Automatically sync any JSON field configuration.
	 */
	public function sync_fields_with_json() {
		if ( defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) ) {
			return;
		}
		
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return;
		}
		
		$version = get_option( 'rb_acf_json_version' );
		
		if ( defined( 'RB_STARTER_VERSION' ) && version_compare( RB_STARTER_VERSION, $version ) ) {
			update_option( 'rb_acf_json_version', RB_STARTER_VERSION );
			$groups = acf_get_field_groups();
			
			if ( empty( $groups ) ) {
				return;
			}
			
			$sync = array();
			foreach ( $groups as $group ) {
				$local    = acf_maybe_get( $group, 'local', false );
				$modified = acf_maybe_get( $group, 'modified', 0 );
				$private  = acf_maybe_get( $group, 'private', false );
				
				if ( $local !== 'json' || $private ) {
					// ignore DB / PHP / private field groups
					continue;
				}
				
				if ( ! $group['ID'] ) {
					$sync[ $group['key'] ] = $group;
				} elseif ( $modified && $modified > get_post_modified_time( 'U', true, $group['ID'], true ) ) {
					$sync[ $group['key'] ] = $group;
				}
			}
			
			if ( empty( $sync ) ) {
				return;
			}
			
			foreach ( $sync as $key => $v ) {
				if ( acf_have_local_fields( $key ) ) {
					$sync[ $key ]['fields'] = acf_get_local_fields( $key );
				}
				acf_import_field_group( $sync[ $key ] );
			}
		}
	}
	
	/**
	 * Register Options Page
	 *
	 */
	function register_options_page() {
		if ( function_exists( 'acf_add_options_page' ) ) {
			acf_add_options_page( array(
				'title'      => __( 'Site Options', 'rb-starter' ),
				'capability' => 'manage_options',
			) );
		}
	}
	
	/**
	 * Register Blocks
	 * @link https://www.billerickson.net/building-gutenberg-block-acf/#register-block
	 *
	 * Categories: common, formatting, layout, widgets, embed
	 * Dashicons: https://developer.wordpress.org/resource/dashicons/
	 * ACF Settings: https://www.advancedcustomfields.com/resources/acf_register_block/
	 */
	function register_blocks() {
		
		if ( ! function_exists( 'acf_register_block_type' ) ) {
			return;
		}
		
		acf_register_block_type( array(
			'name'            => 'test-block',
			'title'           => __( 'Test Block', 'rb-starter' ),
			'render_template' => 'partials/blocks/test.php',
			'category'        => 'formatting',
			'icon'            => 'admin-users',
			'mode'            => 'auto',
			'keywords'        => array( 'test' )
		) );
		
	}
	
	/**
	 * Sets the save directory to the Child Theme acf-json folder
	 */
	public function child_theme_save_field_groups( $path ) {
		return get_stylesheet_directory() . '/acf-json';
	}
}

new RB_ACF_Customizations();
