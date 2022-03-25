<?php

/**
 * Small custom library for rendering and processing options (including tabs and option pages) Woo+WP native way
 *
 * @Stim, 2022
 *
 * For notes and "how to's" - see https://github.com/Wetail/wwoptions
 */

/**
 * Global scope functions
 */
namespace {

	use Wetail\Options\Automation\Renderer;

	defined( 'ABSPATH' ) or exit;

	if( ! function_exists( 'wwp_options' ) )  {
		function wwp_options( $options_pages = [] ) {
            Renderer::wpo_pages( $options_pages );
		}
	}

	if( ! function_exists( 'wwc_options' ) ){
		function wwc_options( $options_tabs = [] ){
            Renderer::wco_tabs( $options_tabs );
		}
	}


}

/**
 * Custom renderer
 */
namespace Wetail\Options\Automation {

	/**
	 * A prototype class for virtualization on WP options page/Woo settings tab
     *
     * Includes all parameters/script-style enqueuers
	 */
	abstract class WPO {

		protected $tabs, $title, $slug, $options, $separate, $save_button, $scripts, $styles, $type;

		function __construct( $slug, $data, $type = 'page' ) {
			$this->title = $data['title'] ?? 'Unknown';
			$this->slug        = $slug;
            $this->type        = $type;
			$this->scripts     = $data['scripts'] ?? [];
			$this->styles      = $data['styles' ] ?? [];
			$this->separate    = ( 'yes' === ( $data['separate_tabs'] ?? '' ) );
			$this->save_button = $data['save_button'] ?? __( 'Save' );
			$this->set_options( $data['options'] );
			add_action( 'admin_head',               [ $this, 'add_head_scripts' ] );
			add_action( 'admin_footer',             [ $this, 'add_foot_scripts' ] );
			add_action( 'admin_enqueue_scripts',    [ $this, 'enqueue_scripts'  ] );
		}

		protected function set_options( $options ) {
			$this->options = $options;
		}

		function enqueue_scripts(){
			foreach( [ 'scripts', 'styles' ] as $type )
				foreach( $this->{ $type } as $slug=>$data )
					if( 'file' === ( $data['type'] ?? 'file' ) )
						if( ( $data['global'] ?? false ) || ( ( $_GET[ $this->type ] ?? '' ) === $this->slug ) )
							Renderer::enqueue_script( $slug, $data, $type );
		}

		protected function render_scripts( $in_footer = false ){
            $right_spot = ( ( $data['footer'] ?? false ) && $in_footer ) || ! $in_footer;
			foreach( [ 'scripts', 'styles' ] as $type )
				foreach( $this->{ $type } as $slug=>$data )
					if( 'inline' === ( $data['type'] ?? 'file' ) && $right_spot )
						if( ( $data['global'] ?? false ) || ( ( $_GET[ $this->type ] ?? '' ) === $this->slug ) )
							Renderer::render_script( $slug, $data, $type );
		}

		function add_head_scripts(){
            self::render_scripts();
		}

		function add_foot_scripts(){
			self::render_scripts( true );
		}

	}

	/**
	 * Class WPO_Page
     *
     * Representing options page for WordPress
     *
	 * @package Wetail\Options\Automation
	 */
	final class WPO_Page extends WPO {

        protected $menu = '', $priority = null, $page_hook = null, $before, $after, $description, $save_notice;

		function __construct( $slug, $data ){
			parent::__construct( $slug, $data );
            $this->set_options( $data['options'] );
            $this->menu         = esc_html( $data['menu'] ?? $this->title );
            $this->priority     = $data['priority'] ?? null;
            $this->before       = $data['before'] ?? '';
            $this->after        = $data['after'] ?? '';
            $this->description  = $data['description'] ?? '';
            $this->save_notice  = $data['save_notice'] ?? '';
			add_action( 'admin_menu',           [ $this, 'create_page'      ] );
            add_action( 'wp_ajax_wwo-' . $slug, [ $this, 'update_options'   ] );
            add_action( 'admin_init',           [ $this, 'update_options'   ] );
            add_action( 'admin_head',           [ $this, 'main_scripts'     ] );
            add_action( 'admin_notices',        [ $this, 'show_notice'      ] );
		}

        function show_notice(){
            if( ! get_transient( 'wwo-updated-' . $this->slug ) ) return;
            delete_transient( 'wwo-updated-' . $this->slug );
            if( empty( $this->save_notice ) ) return;
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php echo $this->save_notice ?>
                </p>
            </div>
            <?php
        }

        function create_page(){
	        $this->page_hook = add_options_page( // pagehook may be used later for rendering "Help"
		        esc_html( $this->title ), // we dont need any tags here
		        esc_html( $this->menu  ),
		        'manage_options',
		        $this->slug,
		        [ $this, 'render' ],
		        $this->priority
	        );
        }

		/**
		 * @NOTE To keep this library as a single-php-script we do this like this!
		 */
        function main_scripts(){
            if( $this->slug !== ( $_GET['page'] ?? false ) ) return;
            ?>
            <style id="wwo_styles_<?echo $this->slug ?>_main">
                .wwp-options input:not([type=checkbox],[type=radio]), .wwp-options select {
                    width: 100%;
                    max-width: 400px;
                }
            </style>
            <?php
            if( $this->separate ) return; // we dont need any JS on separate tabs
            ?>
            <style id="wwo_styles_<?echo $this->slug ?>">
                .wwo-tab-content:not(.active) { display: none }
            </style>
            <script type="text/javascript">
              jQuery.noConflict()( document ).ready( $ => {
                $( '.wwp-options .nav-tab-wrapper a' ).off().on( 'click keydown mousedown', function( e ){
                  $( '.wwp-options .nav-tab-wrapper a' ).removeClass( 'nav-tab-active' );
                  $( '.wwo-tab-content' ).removeClass( 'active' );
                  $( this ).addClass( 'nav-tab-active' );
                  $( '#' + $( this ).data( 'tab') ).addClass( 'active' );
                  window.history.pushState( null, $( this ).text(), $( this ).attr( 'href' ) );
                  if( e ) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                  }
                } );
                $( '.wwp-options .nav-tab-wrapper a.nav-tab-active' ).first().trigger( 'click' );
              } )
            </script>
            <?php
        }

        function update_options(){
            if( ! isset( $_POST[ $this->slug . '_save_wwo_options' ] ) ||
                ! wp_verify_nonce( $_POST[ $this->slug . '_save_wwo_options' ], $this->slug ) ) return;
            unset( $_POST[ $this->slug . '_save_wwo_options' ] );
            foreach( $_POST as $key=>$values )
                if( ! empty( $key ) )
                    update_option( $key, $values );
            set_transient( 'wwo-updated-' . $this->slug, 'true' );
            wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
            die();
        }

        protected function set_options( $options ) {
	        $this->tabs = [];
            $selected_tab = false;
            foreach( $options as $slug=>$option ) {
	            if ( empty( $option['type'] ) ) {
		            $option['type'] = 'text'; // default type
	            }
                if( 'tab' === $option['type'] ) {
                    if( ! isset( $_GET['tab'] ) && ! $selected_tab )
                        $selected_tab = $slug;
	                else
                        $selected_tab = false;
                    if( $slug === ( $_GET['tab'] ?? $selected_tab ) )
	                    $option['selected'] = $selected_tab = $slug;
                    $this->tabs[ $slug ] = $option;

                }
                if ( $this->separate && ! $selected_tab ) {
                    continue;
                }
                $this->options[ $slug ] = $option;
            }
        }

		function render(){
			?>
			<div class="wrap wwp-options <?php echo $this->slug ?>">

				<h1><?php echo $this->before . $this->title . $this->after ?></h1>
                <p class="description"><?php echo $this->description ?></p>

				<hr class="clear-both" />

				<?php if( $this->tabs ) : ?>

					<h2 class="nav-tab-wrapper">
						<?php
                            foreach ( $this->tabs as $tab_slug=>$tab ) :
                            $tab_class = "nav-tab nav-tab-$tab_slug " .
                                         ( ( $tab['selected'] ?? false ) ? 'nav-tab-active' : '' );
                            ?>
							<a class="<?php echo $tab_class ?>"
                               data-tab="<?php echo $tab_slug ?>"
							   href="<?php echo admin_url(
								        'options-general.php?page=' . $this->slug . '&tab=' . $tab_slug
                                     ) ?>">
								<?php echo $tab['title'] ?>
							</a>
						<?php endforeach; ?>
					</h2>

				<?php endif; ?>

				<form method="post"
                      id="<?php echo $this->slug ?>"
                      action="<?php echo admin_url( 'options.php' ) ?>"
                      enctype="multipart/form-data">

                    <input type="hidden" name="<?php echo $this->slug ?>_save_wwo_options"
                           value="<?php echo wp_create_nonce( $this->slug ) ?>" />

                    <?php
                    foreach( $this->options as $slug=>$option )
                        Renderer::output_option( $slug, $option, $opened_tags );

                    Renderer::close_tags( $opened_tags );
                    ?>

                    <?php if ( false !== $this->save_button ) : ?>
                    <p class="submit">
                        <button class="button-primary">
                            <?php echo $this->save_button ?>
                            <span class="wwo-save-icon"></span>
                        </button>
                    </p>
                    <?php endif; ?>

				</form>
			</div>
			<?php
		}
	}

	/**
	 * Class WCO_Tab
     *
     * Representation for WooCommerce settings tab
     *
	 * @package Wetail\Options\Automation
	 */
	final class WCO_Tab extends WPO {

        protected $after, $before, $priority;

		function __construct( $slug, $tab_data ){
            parent::__construct( $slug, $tab_data, 'tab' );
            $this->priority = $tab_data['priority'] ?? 10;
            $this->after    = $tab_data['after']    ?? '';
            $this->before   = $tab_data['before']   ?? '';
			add_filter( 'woocommerce_settings_tabs_array',     [ $this, 'add_settings_tab'  ], $this->priority );
			add_action( 'woocommerce_settings_tabs_' . $slug,  [ $this, 'render_settings'   ] );
			add_action( 'woocommerce_update_options_' . $slug, [ $this, 'update_settings'   ] );
		}

		/**
		 * Add a new settings tab to the WooCommerce settings tabs array.
		 */
		function add_settings_tab( $settings_tabs ) {
            $new_tabs = [];
            if( ! empty( $this->after ) || ! empty( $this->before ) ) {
	            foreach ( $settings_tabs as $slug => $tab ) {
		            if ( $slug === $this->before ) {
			            $new_tabs[ $this->slug ] = $this->title;
		            }
		            $new_tabs[ $slug ] = $tab;
		            if ( $slug === $this->after ) {
			            $new_tabs[ $this->slug ] = $this->title;
		            }
	            }
                return $new_tabs;
            }
			$settings_tabs[ $this->slug ] = $this->title;
			return $settings_tabs;
		}

		/**
		 * Render our settings tab
		 */
		function render_settings() {
			woocommerce_admin_fields( $this->options );
		}

		/**
		 * Update our custom settings
		 */
		function update_settings() {
			woocommerce_update_options( $this->options );
		}
	}

	/**
	 * Class Renderer
     *
     * Helper class to render all custom controls in a native WP way
     *
	 * @package Wetail\Options\Automation
	 */
	final class Renderer {

		static function close_tags( &$opened_tags ){
			if( empty( $opened_tags ) ) return;
			$i = count( $opened_tags );
			while( $i-- ) {
				$tag = $opened_tags[ $i ];
				echo "</$tag>";
			}
            $opened_tags = [];
		}

        static function open_tag( $tag, &$opened_tags ){
            if( ! isset( $opened_tags ) )
                $opened_tags = [];
            $opened_tags[] = $tag;
        }

        static function close_tag( $tag, &$opened_tags ){
            if( empty( $opened_tags ) ) return;
            $output = '';
	        $i = count( $opened_tags );
            while( $i-- ) {
                $_tag = $opened_tags[ $i ];
                $output .= "</$_tag>";
                if( $_tag === $tag ) {
                    if( $i )
                        $opened_tags = array_slice( $opened_tags, 0, $i + 1 );
                    else
                        $opened_tags = [];
	                echo $output;
	                return;
                }
            }
        }

        static function prepare_attributes( $slug, $option, &$attributes ){
	        $attributes  = ' id="' . $slug . '" name="' . $slug . '"';
	        if( ! empty( $option['css'] ) )
		        $attributes .= ' style="' . $option['css'] . '"';
	        foreach( ( $option['custom_attributes'] ?? [] ) as $attr=>$value )
		        $attributes .= ' ' . esc_html( $attr ) . '="' . esc_html( $value ) . '"';
	        if( ! empty( $option[ 'class' ] ) )
		        $attributes .= ' class="' . esc_html( $option['class'] ) .'"';
            if( ! empty( $option[ 'hint' ] ) )
		        $attributes .= ' title="' . esc_html( $option['hint'] ) .'"';
	        if( ! empty( $option['min'] ) )
		        $attributes .= ' min="' . esc_html( $option['min'] ) . '"';
	        if( ! empty( $option['max'] ) )
		        $attributes .= ' max="' . esc_html( $option['max'] ) . '"';
	        if( ! empty( $option['step'] ) )
		        $attributes .= ' step="' . esc_html( $option['step'] ) . '"';
	        if( ! empty( $option['pattern'] ) )
		        $attributes .= ' pattern="' . esc_html( $option['pattern'] ) . '"';
	        if( ! empty( $option['placeholder'] ) )
		        $attributes .= ' placeholder="' . esc_html( $option['placeholder'] ) . '"';
        }

        static function render_control( $slug, $option ){

	        self::prepare_attributes( $slug, $option, $attributes );

	        $value = $option['value'] ?? ( ( $v = get_option( $slug ) ) ? $v : ( $option['default'] ?? '' ) );

	        $label = $option['label'] ?? '';

	        $before = $option['before'] ?? '';
	        $after  = $option['after' ] ?? '';

	        switch( $option['type'] ){

		        case 'select':
			        echo ( $label ? $label . '<br/>' : '' );
			        $options = '';
			        foreach ( ( $option['options'] ?? [] ) as $v=>$t )
				        $options .=
					        '<option value="' . $v . '" ' . ( $v === $value ? 'selected' : '' ) . '>' .
					            $t .
					        '</option>';
			        echo "$before<select$attributes>$options</select>$after";
                    break;

		        case 'radio':
			        echo ( $label ? $label . '<br/>' : '' );
			        foreach( ( $option['options'] ?? [] ) as $v=>$t )
				        echo $before . '<label>' .
				             '<input type="radio" ' . ( $value === $v ? 'checked' : '' ) .
				                   ' value="' . $v . '" ' . $attributes . '/> ' .
				             $t .
				             '</label>' . $after;
			        break;

		        case 'group':
                    echo '<fieldset>';
			        echo ( $label ? $label . '<br/>' : '' );
			        foreach( ( $option['options'] ?? [] ) as $s=>$o ) {
				        self::render_control( $s, $o );
				        echo '<br/>';
			        }
                    echo '</fieldset>';
                    break;

		        case 'checkbox':
			        echo $before . '<label>' .
			             '<input type="hidden" value="no" name="' . $slug . '" />' .
			             '<input type="checkbox" ' . ( $value === 'yes' ? 'checked' : '' ) .
			             ' value="yes" ' . $attributes . '/> ' . $label .
			             '</label>' . $after;
			        break;

		        case 'button':
			        echo "$before<button$attributes value=\"$value\">$label</button>$after";
			        break;

		        case 'custom':
                    if( is_callable( $value ) )
                        try {
	                        $value = call_user_func( $value );
                        } catch ( \Throwable $t ){
                            error_log( $t->getMessage() );
                            $value = '';
                        }
			        echo $before . $value . $after;
			        break;

		        case 'textarea':
			        echo ( $label ? $label . '<br/>' : '' );
			        echo $before . '<textarea ' . $attributes . '>' . $value . '</textarea>' . $after;
			        break;

		        default:
			        echo ( $label ? $label . '<br/>' : '' );
			        echo $before .
			             '<input type="' . $option['type'] . '" value="' . $value . '" ' . $attributes . ' />' .
			             $after;
	        }

        }

        static function output_option( $slug, $option, &$opened_tags ){
            switch( $option['type'] ){
                case 'tab':
                    self::close_tags( $opened_tags );
                    echo '<div id="' . $slug . '" class="wwo-tab-content">';
                    self::open_tag( 'div', $opened_tags );
                    break;

                case 'section':
                    self::close_tag( 'table', $opened_tags );
                    echo '<h2 class="title">' . $option['title'] . '</h2>';
                    if( ! empty( $option['description'] ) )
                        echo '<p class="description">' . $option['description'] . '</p>';
                    echo '<table class="form-table"><tbody>';
                    self::open_tag( 'table', $opened_tags );
                    self::open_tag( 'tbody', $opened_tags );
                    break;

                case 'section_end':
                    self::close_tag( 'table', $opened_tags );
                    echo '<hr class="clear-both"/>';
                    break;

                default :
                    echo '<tr>' .
                         '<th scope="row">' .
                         '<label for="' . $slug . '">' . ( $option['title'] ?? '' ) . '</label>' .
                         '</th>' .
                         '<td>';

                    self::render_control( $slug, $option );

                    if( ! empty( $option['description'] ) )
                        echo '<p class="description">' . $option['description'] . '</p>';

                    echo '</td></tr>';
            }
        }

		/**
         * Enqueue single script or style in WP way
         *
		 * @param $slug
		 * @param $data
		 * @param string $type
		 */
		static function enqueue_script( $slug, $data, $type = 'scripts' ){
            if( 0 !== strpos( trim( $data['source'] ), 'http' ) ) return;
            if( $type === 'styles' ){
                wp_enqueue_style( $slug, $data['source'], $data['deps'] ?? [], time() );
                return;
            }
            wp_register_script( $slug, $data['source'], $data['deps'] ?? [], time(), $data['footer'] ?? false );
            if( ! empty( $data['localize'] ) )
                foreach ( $data['localize'] as $obj=>$values )
                    wp_localize_script( $slug, $obj, $values );
            wp_enqueue_script( $slug );
		}

		/**
         * Render script inline way
         *
		 * @param $slug
		 * @param $data
		 * @param string $type
		 */
		static function render_script( $slug, $data, $type = 'scripts' ){
            if( empty( $data['source'] ) ) return;
			if( 'styles' === $type ) {
				echo '<style id="wwo-css-' . $slug . '">' . $data['source'] . '</style>';
                return;
			}
            if( ! empty( $data['localize'] ) )
                foreach( $data['localize'] as $obj=>$values )
	                $data['source'] = 'let ' . $obj . '=' . json_encode( $values ) . ";\n" . $data['source'];
			echo '<script type="text/javascript" id="wwo-js-' . $slug . '">' . $data['source'] . '</script>';
		}

		/**
         * Initialize/create all WP options pages
         *
		 * @param $options
		 */
	    static function wpo_pages( $options ){
		    if( ! self::check_hook() ) return;
            $pages = []; $current_slug = false;
            foreach( $options as $slug=>$option )
                if( ( $option['type'] ?? '' ) === 'page' ) {
                    $current_slug = $slug;
                    $option['options'] = [];
	                $pages[ $slug ] = $option;
                } elseif( $current_slug ) {
                    $pages[ $current_slug ]['options'][ $slug ] = $option;
                }
            foreach ( $pages as $slug=>$page_options )
                new WPO_Page( $slug, $page_options );
	    }

		/**
         * Initialize/create all Woo settings tabs
         *
		 * @param $options
		 */
        static function wco_tabs( $options ){
            if( ! self::check_hook() ) return;
	        $tabs = []; $current_slug = false;
	        foreach( $options as $slug=>$option )
		        if( ( $option['type'] ?? '' ) === 'tab' ) {
			        $current_slug = $slug;
			        $option['options'] = [];
			        $tabs[ $slug ] = $option;
		        } elseif( $current_slug ) {
			        $tabs[ $current_slug ]['options'][ $slug ] = $option;
		        }
	        foreach ( $tabs as $slug=>$tab_options )
		        new WCO_Tab( $slug, $tab_options );
        }

        private static function check_hook(){
	        if( did_action( 'woocommerce_init' ) ) {
		        error_log(
			        '[WWO_OPTIONS] Functions "wwp_options" and "wwc_options" must be called before or on ' .
                    '"woocommerce_init" action hook!'
		        );

		        return false;
	        }
	        return true;
        }

	}

}


