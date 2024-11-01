<?php
/**
 * All Shortcode related functions
 */
namespace Codexpert\WC_Affiliate;
use Codexpert\Plugin\Base;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @package Plugin
 * @subpackage Shortcode
 * @author codexpert <hello@codexpert.io>
 */
class Shortcode extends Base {

    public $plugin;

    public $slug;

    public $name;
    
    public $version;

    /**
     * Constructor function
     */
    public function __construct( $plugin ) {
        $this->plugin   = $plugin;
        $this->slug     = $this->plugin['TextDomain'];
        $this->name     = $this->plugin['Name'];
        $this->version  = $this->plugin['Version'];
    }

    public function dashboard() {
        return Helper::get_template( 'dashboard', 'views/front/shortcodes', [ 'plugin' => $this->plugin ] );
    }

    public function application_form() {
        return Helper::get_template( 'application-form', 'views/front/shortcodes', [ 'plugin' => $this->plugin ] );
    }
}