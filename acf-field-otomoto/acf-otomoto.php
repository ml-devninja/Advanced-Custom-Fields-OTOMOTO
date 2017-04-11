<?php

/*
Plugin Name: Advanced Custom Fields: OTOMOTO
Plugin URI: PLUGIN_URL
Description: Enable to select adverts from OTOMOTO
Version: 1.0.0
Author: Mateusz Lewandowski
Author URI: http://dev-ninja.pl
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('acf_plugin_otomoto') ) :

class acf_plugin_otomoto {
	
	/*
	*  __construct
	*
	*  This function will setup the class functionality
	*
	*  @type	function
	*  @date	17/02/2016
	*  @since	1.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function __construct() {
		
		// vars
		$this->settings = array(
			'version'	=> '1.0.0',
			'url'		=> plugin_dir_url( __FILE__ ),
			'path'		=> plugin_dir_path( __FILE__ )
		);



        // Load custom assets on front-end layer
        add_action( 'wp_head', array( &$this, 'otomoto_libs' ) );
        // Plugin backend menu item
        add_action( 'admin_menu', array( &$this, 'otomoto_menu' ) );
        // Register Settings
        add_action( 'admin_init', array( &$this, 'otomoto_settings' ) );

        add_action( 'admin_enqueue_scripts', array( &$this, 'otomoto_color_picker' ) );



        // set text domain
		// https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
		load_plugin_textdomain( 'acf-otomoto', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' );


		
		// include field
		add_action('acf/include_field_types', 	array($this, 'include_field_types')); // v5
		add_action('acf/register_fields', 		array($this, 'include_field_types')); // v4
		
	}

    /*--------------------------------------------*
     * Admin Menu
     *--------------------------------------------*/

    function otomoto_menu()
    {
        $page_title = __('ACF OTOMOTO', 'otomoto');
        $menu_title = __('ACF OTOMOTO', 'otomoto');
        $capability = 'manage_options';
        $menu_slug = 'otomoto-options';
        $function = array(&$this, 'otomoto_menu_contents');
        add_options_page($page_title, $menu_title, $capability, $menu_slug, $function);

    }

    /**
     * Include color picker
     */

    function otomoto_color_picker( $hook ) {

        if( is_admin() ) {
            // Add the color picker css file
            wp_enqueue_style( 'wp-color-picker' );

            // Include our custom jQuery file with WordPress Color Picker dependency
            wp_enqueue_script( 'otmoto_color_picker', plugins_url( 'assets/js/color-picker.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
        }

        if($_GET['settings-updated'] === 'true'){
            $options = get_option( 'otomoto_settings' );
            $color = $options["main_color"];
            $path = plugin_dir_path( __FILE__ );
            require "assets/css/lessc.inc.php";
            $less = new lessc;
            $less->setVariables(array(
                "main" => $color
            ));
            $css =  $less->compileFile($path."assets/css/otomoto.less");
            file_put_contents($path."assets/css/otomoto.css", $css);


            }
    }

    /**
     * Enqueue Theme for adverts
     */

    function otomoto_libs(){
        wp_enqueue_style( 'otomoto', plugins_url('/acf-field-otomoto/assets/css/otomoto.css') );
    }


    /*--------------------------------------------*
    * Settings & Settings Page
    * dot_cfi_menu_contents
    *--------------------------------------------*/

    public function otomoto_menu_contents()
    {
        ?>
        <div class="wrap">
            <h2><?php _e('ACF: OTOMOTO', 'otomoto'); ?></h2>
            <p class="small">Wprowadź dane aby wyświetlać oferty z OTOMOTO na Twojej stronie.</p>

            <form method="post" action="options.php">
                <?php //wp_nonce_field('update-options'); ?>
                <?php settings_fields('otomoto_settings'); ?>
                <?php do_settings_sections('otomoto_settings'); ?>
                <p class="submit">
                    <input name="Submit" type="submit" class="button-primary"
                           value="<?php _e('Zapisz zmiany', 'otomoto'); ?>"/>
                </p>
            </form>
            <pre>
            <?php // $options = get_option( 'otomoto_settings' ); var_dump($options); ?>
            </pre>
        </div>

        <?php
    }


    function otomoto_settings()
    {
        register_setting('otomoto_settings', 'otomoto_settings');

        // SECTIONS
        add_settings_section('dev_data', 'Dane w trybie testowym', array(&$this, 'dev_data'), 'otomoto_settings');
        add_settings_section('prod_data', 'Dane w trybie produkcyjnym', array(&$this, 'prod_data'), 'otomoto_settings');
        add_settings_section('keys', 'ID oraz klucz', array(&$this, 'keys'), 'otomoto_settings');
        add_settings_section('colors', 'Kolory', array(&$this, 'colors'), 'otomoto_settings');

        // FIELDS
        add_settings_field('dev_login', 'Login', array( &$this, 'dev_login' ), 'otomoto_settings', 'dev_data');
        add_settings_field('dev_pass', 'Hasło', array( &$this, 'dev_pass' ), 'otomoto_settings', 'dev_data');

        add_settings_field('prod_login', 'Login', array( &$this, 'prod_login' ), 'otomoto_settings', 'prod_data');
        add_settings_field('prod_pass', 'Hasło', array( &$this, 'prod_pass' ), 'otomoto_settings', 'prod_data');

        add_settings_field('test_mode', 'Tryb testowy', array( &$this, 'test_mode' ), 'otomoto_settings', 'dev_data');

        add_settings_field('key_id', 'ID', array( &$this, 'key_id' ), 'otomoto_settings', 'keys');
        add_settings_field('key_secret', 'SECRET', array( &$this, 'key_secret' ), 'otomoto_settings', 'keys');

        add_settings_field('main_color', 'Podstawowy kolor', array( &$this, 'main_color' ), 'otomoto_settings', 'colors');
//        add_settings_field('key_secret', 'SECRET', array( &$this, 'key_secret' ), 'otomoto_settings', 'keys');

    }

    function colors(){}
    function main_color(){
        $options = get_option( 'otomoto_settings' ); ?>

        <span class='text box'>
            <label for="otomoto_settings[main_color]">
                <input type='text' id='otomoto_settings[main_color]' class='color-field' name='otomoto_settings[main_color]' value='<?php echo $options["main_color"]; ?>'/>
            </label>
        </span>

     <?php }

    function keys(){}
    function key_id() {
        $options = get_option( 'otomoto_settings' );
        ?>
        <span class='text box'>
            <label for="otomoto_settings[key_id]">
                <input type='text' id='otomoto_settings[key_id]' class='regular-text' name='otomoto_settings[key_id]' value='<?php echo $options["key_id"]; ?>'/>
            </label>
        </span>
        <?php
    }
    function key_secret() {
        $options = get_option( 'otomoto_settings' );
        ?>
        <span class='text box'>
            <label for="otomoto_settings[key_secret]">
                <input type='password' id='otomoto_settings[key_secret]' class='regular-text' name='otomoto_settings[key_secret]' value='<?php echo $options["key_secret"]; ?>'/>
            </label>
        </span>
        <?php
    }





    function dev_data(){}
    function dev_login() {
        $options = get_option( 'otomoto_settings' );
        ?>
        <span class='text box'>
            <label for="otomoto_settings[dev_login]">
                <input type='text' id='otomoto_settings[dev_login]' class='regular-text' name='otomoto_settings[dev_login]' value='<?php echo $options["dev_login"]; ?>'/>
            </label>
        </span>
        <?php
    }
    function dev_pass() {
        $options = get_option( 'otomoto_settings' );
        ?>
        <span class='text box'>
            <label for="otomoto_settings[dev_pass]">
                <input type='password' id='otomoto_settings[dev_pass]' class='regular-text' name='otomoto_settings[dev_pass]' value='<?php echo $options["dev_pass"]; ?>'/>
            </label>
        </span>
        <?php
    }







    function prod_data(){}
    function prod_login() {
        $options = get_option( 'otomoto_settings' );
        ?>
        <span class='text box'>
            <label for="otomoto_settings[prod_login]">
                <input type='text' id='otomoto_settings[prod_login]' class='regular-text' name='otomoto_settings[prod_login]' value='<?php echo $options["prod_login"]; ?>'/>
            </label>
        </span>
        <?php
    }
    function prod_pass() {
        $options = get_option( 'otomoto_settings' );
        ?>
        <span class='text box'>
            <label for="otomoto_settings[prod_pass]">
                <input type='password' id='otomoto_settings[prod_pass]' class='regular-text' name='otomoto_settings[prod_pass]' value='<?php echo $options["prod_pass"]; ?>'/>
            </label>
        </span>
        <?php
    }







    function test_mode() {
        $options = get_option( 'otomoto_settings' );
        ?>
        <span class='checkbox'>
            <label for="headtype_checkbox_1">
                <input type="checkbox" id="headtype_checkbox_1" name="otomoto_settings[test_mode]" value="on" <?php checked('on', $options['test_mode']); ?>/>
                TEST
            </label>
        </span>
        <?php
    }



	/*
	*  include_field_types
	*
	*  This function will include the field type class
	*
	*  @type	function
	*  @date	17/02/2016
	*  @since	1.0.0
	*
	*  @param	$version (int) major ACF version. Defaults to false
	*  @return	n/a
	*/
	
	function include_field_types( $version = false ) {
		
		// support empty $version
		if( !$version ) $version = 4;
		
		
		// include
		include_once('fields/acf-otomoto-v' . $version . '.php');
		
	}
	
}


// initialize
new acf_plugin_otomoto();


// class_exists check
endif;
	
?>