<?php

/**
 * Plugin Name: Some Redirects
 * Description: Quick'n'dirty redirects from the backend
 * Version:     0.1
 * Author:      David Naber
 * Author URI:  http://dnaber.de/
 */

if ( ! function_exists( 'add_filter' ) )
	exit;

add_action( 'init', array( 'Some_Redirects', 'init' ) );
register_activation_hook( __FILE__, array( 'Some_Redirects', 'activate' ) );

class Some_Redirects {

	const VERSION = '0.1';

	const OPTION_KEY = 'some_redirects';

	public static $dir = '';

	protected $options = array();

	/**
	 * @var Stettings_API_Helper
	 */
	protected $settings = NULL;

	/**
	 * on activation
	 */
	public static function activate() {

		# disable redirection on activation
		$opt = get_option( self::OPTION_KEY, array() );
		if ( ! isset( $opt[ 'enable_redirection' ] ) )
			return;

		$opt[ 'enable_redirection' ] = '0';
		update_option( self::OPTION_KEY, $opt );

	}

	/**
	 * init
	 *
	 * @return void
	 */
	public function init() {

		self::$dir = dirname( __FILE__ );
		require_once self::$dir . '/php/settings-api-helper/load.php';
		new self;
	}

	public function __construct() {


		add_action( 'admin_init', array( $this, 'init_settings' ) );
		add_action( 'admin_init', array( $this, 'load_options' ), 11 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		$this->load_options();
		$this->redirect();

	}

	public function init_settings() {

		$desc  = 'Pro Zeile eine Weiterleitung nach dem Schema:' . "\n";
		$desc .= '<code><em>Statuscode</em> <em>Quelle</em>[ <em>Ziel</em>]</code>' . "\n";
		$desc .= 'Mögliche Statuscodes: 301, 302, 410.' . "\n";
		$desc .= 'Die Quelle muss ohne Domain angegeben werden, das Ziel mit! Bis auf 410 muss auch ein Ziel angegeben werden.' . "\n";
		$desc .= '<code>302 /some_page.php http://www.domain.tld/new_page/</code>';

		$this->settings = new Settings_API_Helper(
			self::OPTION_KEY,
			'redirects',
			'Umleitungen',
			$desc
		);
		$this->settings->add_checkbox(
			'enable_redirection',
			'Weiterleitung aktivieren',
			array(
				'default' => '0'
			)
		);

		$this->settings->add_textarea(
			'rules',
			'Weiterleitungen',
			array(
				'required' => FALSE,
				'default'  => '',
				'class'    => array( 'large-text', 'code' ),
				'atts'     => array( 'rows' => '20' )
			)
		);

	}

	public function load_options() {

		$this->options = get_option( self::OPTION_KEY, array() );
	}

	public function admin_menu() {

		add_submenu_page(
			'tools.php',
			'Weiterleitungen',
			'Weiterleitungen',
			'manage_options',
			'redirects',
			array( $this, 'menu_page' )
		);
	}

	public function menu_page() {

		?>
		<div class="inside">
			<?php $this->settings->the_form(); ?>
		</div>
		<?php
	}

	protected function redirect() {

		if ( ! isset( $this->options[ 'enable_redirection' ] )
		  || '1' !== $this->options[ 'enable_redirection' ] )
			return;

		# don't redirect backend urls
		if ( is_admin() )
			return;

		$request = $_SERVER[ 'REQUEST_URI' ];
		#strip query string
		#$request = str_replace( '?' . $_SERVER[ 'QUERY_STRING' ], '', $request );
		$rules = explode( "\n", $this->options[ 'rules' ] );
		$stati = array( '301', '302', '410' );

		foreach ( $rules as $rule ) {
			$rule = trim( $rule );
			$rule = $this->chunk_rule( $rule );
			if ( $request !== $rule[ 'path' ] )
				continue;

			if ( ! in_array( $rule[ 'status' ], $stati ) )
				$rule[ 'status' ] = 301;
			else
				$rule[ 'status' ] = ( int ) $rule[ 'status' ];

			if ( empty( $rule[ 'redirect' ] ) && $rule[ 'status' ] !== 410 )
				continue;


			if ( 410 === $rule[ 'status' ] ) {

				$protocol = 'HTTP/1.1' == $_SERVER[ 'SERVER_PROTOCOL' ]
					? $_SERVER[ 'SERVER_PROTOCOL' ]
					: 'HTTP/1.0';

				header( $protocol . ' 410 - Gone' );
				echo '<h1>410 - Gone</h1>';
				echo '<p>Die gesuchte Seite existiert wurde gelöscht.</p>';
				echo '<p>Bitte nutzen sie <a href="' . home_url( '/' ) . '">' . home_url( '/' ) . '</a>.</p>';
				exit;
			}

			$url = parse_url( $rule[ 'redirect' ] );
			# avoid infinite loops
			if ( ! isset( $url[ 'path' ] ) )
				$url[ 'path' ] = '/';

			$url_clone = rtrim( $url[ 'path' ], '/' );

			if ( $url[ 'path' ] === $request  || $url_clone === $request )
				continue;

			# status is 301 or 302 here
			wp_redirect( $rule[ 'redirect' ], $rule[ 'status' ] );
			exit;
		}

	}

	/**
	 * cunks a single line into an array
	 *
	 * @param string $rule
	 * @return array
	 */
	protected function chunk_rule( $rule = '' ) {

		$rule   = explode( ' ', $rule );
		$return = array(
			'status'   => '',
			'path'     => '',
			'redirect' => ''
		);

		if ( isset( $rule[ 0 ] ) )
			$return[ 'status' ] = trim( $rule[ 0 ] );

		if ( isset( $rule[ 1 ] ) )
			$return[ 'path' ] = trim( $rule[ 1 ] );

		if ( isset( $rule[ 2 ] ) )
			$return[ 'redirect' ] = trim( $rule[ 2 ] );

		return $return;
	}

}
