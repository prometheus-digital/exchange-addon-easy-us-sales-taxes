<?php
/**
 * Member Dashboard class for THEME API in Advanced U.S. Taxes Add-on
 *
 * @package exchange-addon-advanced-us-taxes
 * @since 1.0.0 
*/

class IT_Theme_API_Advanced_US_Taxes implements IT_Theme_API {
	
	/**
	 * API context
	 * @var string $_context
	 * @since 1.0.0 
	*/
	private $_context = 'advanced-us-taxes';

	/**
	 * Current cart in iThemes Exchange Global
	 * @var object $product
	 * @since 0.4.0
	*/
	private $cart;
	
	/**
	 * Maps api tags to methods
	 * @var array $_tag_map
	 * @since 1.0.0 
	*/
	public $_tag_map = array(
		'label' => 'label',
		'tax'   => 'tax',
	);

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function IT_Theme_API_Advanced_US_Taxes() {
	}

	/**
	 * Returns the context. Also helps to confirm we are an iThemes Exchange theme API class
	 *
	 * @since 1.0.0
	 * 
	 * @return string
	*/
	function get_api_context() {
		return $this->_context;
	}

	/**
	 * @since 1.0.0
	 * @return string
	*/
	function label( $options=array() ) {
		$result = '';
		/*
		$membership_settings = it_exchange_get_option( 'addon_membership' );
		
		$defaults      = array(
			'before'       => '',
			'after'        => '',
			'label'        => $membership_settings['membership-intended-audience-label'],
			'before_label' => '<h3>',
			'after_label'  => '</h3>',
			'before_desc'  => '<p>',
			'after_desc'   => '</p>',
		);
		$options = ITUtility::merge_defaults( $options, $defaults );
				
		// Return boolean if has flag was set
		if ( $options['supports'] )
			return it_exchange_product_supports_feature( $this->product->ID, 'membership-information' );

		// Return boolean if has flag was set
		if ( $options['has'] )
			return it_exchange_product_has_feature( $this->product->ID, 'membership-information', array( 'setting' => 'intended-audience' ) );

		// Repeats checks for when flags were not passed.
		if ( it_exchange_product_supports_feature( $this->product->ID, 'membership-information' )	
				&& it_exchange_product_has_feature( $this->product->ID, 'membership-information', array( 'setting' => 'intended-audience' ) ) ) {
			
			$description = it_exchange_get_product_feature( $this->product->ID, 'membership-information', array( 'setting' => 'intended-audience' ) );
			
			if ( !empty( $description ) ) {
			
				$result .= $options['before'];
				$result .= $options['before_label'] . $options['label'] . $options['after_label'];
				$description = wpautop( $description );
				$description = shortcode_unautop( $description );
				$description = do_shortcode( $description );
				$result .= $options['before_desc'] . $description . $options['after_desc'];
				$result .= $options['after'];
				
			}
			
		}
		/**/
		return $result;
	}

	/**
	 * @since 1.0.0
	 * @return string
	*/
	function tax( $options=array() ) {
		$result = '';
		/*
		$membership_settings = it_exchange_get_option( 'addon_membership' );
		
		$defaults      = array(
			'before'       => '',
			'after'        => '',
			'label'        => $membership_settings['membership-objectives-label'],
			'before_label' => '<h3>',
			'after_label'  => '</h3>',
			'before_desc'  => '<p>',
			'after_desc'   => '</p>',
		);
		$options = ITUtility::merge_defaults( $options, $defaults );
				
		// Return boolean if has flag was set
		if ( $options['supports'] )
			return it_exchange_product_supports_feature( $this->product->ID, 'membership-information' );

		// Return boolean if has flag was set
		if ( $options['has'] )
			return it_exchange_product_has_feature( $this->product->ID, 'membership-information', array( 'setting' => 'objectives' ) );

		// Repeats checks for when flags were not passed.
		if ( it_exchange_product_supports_feature( $this->product->ID, 'membership-information' )	
				&& it_exchange_product_has_feature( $this->product->ID, 'membership-information', array( 'setting' => 'objectives' ) ) ) {
			
			$description = it_exchange_get_product_feature( $this->product->ID, 'membership-information', array( 'setting' => 'objectives' ) );
			
			if ( !empty( $description ) ) {
			
				$result .= $options['before'];
				$result .= $options['before_label'] . $options['label'] . $options['after_label'];
				$description = wpautop( $description );
				$description = shortcode_unautop( $description );
				$description = do_shortcode( $description );
				$result .= $options['before_desc'] . $description . $options['after_desc'];
				$result .= $options['after'];
				
			}
			
		}
		/**/
		return $result;
	}

}
