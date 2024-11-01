<?php
/**
 * All common functions to load in both admin and front
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
 * @subpackage Common
 * @author Codexpert <hi@codexpert.io>
 */
class Common extends Base {

    public $plugin;

    public $slug;

    public $name;
    
    public $version;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->version	= $this->plugin['Version'];
	}

	public function show_current_user_attachments( $query = [] )	{
		if( ! current_user_can( 'edit_pages' ) ) {
	        $query['author'] = get_current_user_id();
	    }
	    return $query;
	}

}