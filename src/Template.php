<?php
/**
 * All Template facing functions
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
 * @subpackage Template
 * @author codexpert <hello@codexpert.io>
 */
class Template extends Base {

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

    public function dashboard( $template = 'panel' ) {
        if ( $template == 'panel' ) {
            $template_path = 'views/front/shortcodes/dashboard';
        }
        else{
            $template_path = 'views/front/authenticate';
        }
        echo Helper::get_template( $template, $template_path, [ 'plugin' => $this->plugin ] );
    }

    public function profile() {
        echo Helper::get_template( 'profile', 'views/front/shortcodes/dashboard', [ 'plugin' => $this->plugin ] );
    }

    public function navigation() {
        echo Helper::get_template( 'navigation', 'views/front/shortcodes/dashboard', [ 'plugin' => $this->plugin ] );
    }

    public function tab_content( $tab ) {
        $tab_content = Helper::get_template( $tab, 'views/front/shortcodes/dashboard/tabs', [ 'plugin' => $this->plugin ] );

        echo apply_filters( 'wc-affiliate-shortcodes_dashboard_content', $tab_content, $tab );
    }

    public function report() {
        echo Helper::get_template( 'report', 'views/admin/report', [ 'plugin' => $this->plugin ] );
    }

    public function review_user() {
        echo Helper::get_template( 'review-user', 'views/admin/user', [ 'plugin' => $this->plugin ] );
    }

    public function transaction_form() {
        echo Helper::get_template( 'transaction-form', 'views/admin/user', [ 'plugin' => $this->plugin ] );
    }

    public function new_affiliate_form() {
        echo Helper::get_template( 'new-affiliate-form', 'views/admin/user', [ 'plugin' => $this->plugin ] );
    }
}
