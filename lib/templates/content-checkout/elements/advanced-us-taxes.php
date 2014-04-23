<?php
/**
 * This is the default template for the Advanced U.S. Taxes
 * element in the totals loop of the content-checkout
 * template part. It was added by Advanced U.S. Taxes add-on.
 *
 * @since 1.0.0
 * @version 1.0.0
 * @package exchange-addon-advanced-us-taxes
 *
 * WARNING: Do not edit this file directly. To use
 * this template in a theme, copy over this file
 * to the exchange/content-checkout/elements/
 * directory located in your theme.
*/
?>

<?php do_action( 'it_exchange_content_checkout_before_advanced_us_taxes_element' ); ?>
<div class="it-exchange-cart-totals-title it-exchange-table-column">
	<?php do_action( 'it_exchange_content_checkout_before_advanced_us_taxes_label' ); ?>
	<div class="it-exchange-table-column-inner">
		<?php _e( 'Tax', 'LION' ); ?>
		<?php echo it_exchange_advanced_us_taxes_addon_exemptions(); ?>
	</div>
	<?php do_action( 'it_exchange_content_checkout_after_advanced_us_taxes_label' ); ?>
</div>
<div class="it-exchange-cart-totals-amount it-exchange-table-column">
	<?php do_action( 'it_exchange_content_checkout_before_advanced_us_taxes_value' ); ?>
	<div class="it-exchange-table-column-inner">
		<?php echo it_exchange_advanced_us_taxes_addon_get_taxes_for_cart(); ?>
	</div>
	<?php do_action( 'it_exchange_content_checkout_after_advanced_us_taxes_value' ); ?>
</div>
<?php do_action( 'it_exchange_content_checkout_after_advanced_us_taxes_element' ); ?>
