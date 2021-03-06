<?php
/**
 * Order tracking shortcode
 *
 * DISCLAIMER
 *
 * Do not edit or add directly to this file if you wish to upgrade Jigoshop to newer
 * versions in the future. If you wish to customise Jigoshop core for your needs,
 * please use our GitHub repository to publish essential changes for consideration.
 *
 * @package             Jigoshop
 * @category            Customer
 * @author              Jigowatt
 * @copyright           Copyright © 2011-2012 Jigowatt Ltd.
 * @license             http://jigoshop.com/license/commercial-edition
 */

function get_jigoshop_order_tracking ($atts) {
	return jigoshop_shortcode_wrapper('jigoshop_order_tracking', $atts);
}

function jigoshop_order_tracking( $atts ) {

	extract(shortcode_atts(array(
	), $atts));

	global $post;

	if ($_POST) :

		$order = new jigoshop_order();

		if (isset($_POST['orderid']) && $_POST['orderid'] > 0) $order->id = (int) $_POST['orderid']; else $order->id = 0;
		if (isset($_POST['order_email']) && $_POST['order_email']) $order_email = trim($_POST['order_email']); else $order_email = '';

		if ( !jigoshop::verify_nonce('order_tracking') ):

			echo '<p>'.__('You have taken too long. Please refresh the page and retry.', 'jigoshop').'</p>';

		elseif ($order->id && $order_email && $order->get_order( $order->id )) :

			if ($order->billing_email == $order_email) :

				echo '<p>'.sprintf( __('Order #%s which was made %s has the status &ldquo;%s&rdquo;', 'jigoshop'), $order->id, human_time_diff(strtotime($order->order_date), current_time('timestamp')).__(' ago', 'jigoshop'), $order->status );

				if ($order->status == 'completed') echo __(' and was completed ', 'jigoshop').human_time_diff(strtotime($order->completed_date), current_time('timestamp')).__(' ago', 'jigoshop');

				echo '.</p>';

				?>
				<h2><?php _e('Order Details', 'jigoshop'); ?></h2>
				<table class="shop_table">
					<thead>
						<tr>
							<th><?php _e('ID/SKU', 'jigoshop'); ?></th>
							<th><?php _e('Title', 'jigoshop'); ?></th>
							<th><?php _e('Price', 'jigoshop'); ?></th>
							<th><?php _e('Quantity', 'jigoshop'); ?></th>
						</tr>
					</thead>
					<tfoot>
                        <tr>
                            <?php if ((get_option('jigoshop_calc_taxes') == 'yes' && $order->has_compound_tax())
                                || (get_option('jigoshop_tax_after_coupon') == 'yes' && $order->order_discount > 0)) : ?>
                                <td colspan="3"><?php _e('Retail Price', 'jigoshop'); ?></td>
                            <?php else : ?>
                                <td colspan="3"><?php _e('Subtotal', 'jigoshop'); ?></td>
                            <?php endif; ?>
                                <td><?php echo $order->get_subtotal_to_display(); ?></td>
                        </tr>
                        <?php
                        if ($order->order_shipping>0) : ?>
                            <tr>
                                <td colspan="3"><?php _e('Shipping', 'jigoshop'); ?></td>
                                <td><?php echo $order->get_shipping_to_display(); ?></td>
                            </tr>
                            <?php 
                        endif; 
                        if (get_option('jigoshop_tax_after_coupon') == 'yes' && $order->order_discount > 0) : ?>
                            <tr class="discount">
                                <td colspan="3"><?php _e('Discount', 'jigoshop'); ?></td>
                                <td>-<?php echo jigoshop_price($order->order_discount); ?></td>
                            </tr>
                            <?php 
                        endif; 
                        if ((get_option('jigoshop_calc_taxes') == 'yes' && $order->has_compound_tax())
                         || (get_option('jigoshop_tax_after_coupon') == 'yes' && $order->order_discount > 0)) :  ?>
                            <tr>
                                <td colspan="3"><?php _e('Subtotal', 'jigoshop'); ?></td>
                                <td><?php echo jigoshop_price($order->order_discount_subtotal); ?></td>
                            </tr>
                            <?php 
                        endif;
                        if (get_option('jigoshop_calc_taxes') == 'yes') :
                            foreach ( $order->get_tax_classes() as $tax_class ) :
                                if ($order->show_tax_entry($tax_class)) : ?>
                                    <tr>
                                        <td colspan="3"><?php echo $order->get_tax_class_for_display($tax_class) . ' (' . (float) $order->get_tax_rate($tax_class) . '%):'; ?></td>
                                        <td><?php echo $order->get_tax_amount($tax_class) ?></td>
                                    </tr>
                                    <?php
                                endif;
                            endforeach;
                        endif; ?>
						<?php if (get_option('jigoshop_tax_after_coupon') == 'no' && $order->order_discount>0) : ?><tr class="discount">
							<td colspan="3"><?php _e('Discount', 'jigoshop'); ?></td>
							<td>-<?php echo jigoshop_price($order->order_discount); ?></td>
						</tr><?php endif; ?>
						<tr>
							<td colspan="3"><strong><?php _e('Grand Total', 'jigoshop'); ?></strong></td>
							<td><strong><?php echo jigoshop_price($order->order_total); ?></strong></td>
						</tr>
					</tfoot>
					<tbody>
						<?php
						foreach($order->items as $order_item) :

							if (isset($order_item['variation_id']) && $order_item['variation_id'] > 0) :
								$_product = new jigoshop_product_variation( $order_item['variation_id'] );
							else :
								$_product = new jigoshop_product( $order_item['id'] );
							endif;

							echo '<tr>';

							echo '<td>'.$_product->sku.'</td>';
							echo '<td class="product-name">'.$_product->get_title();

							if (isset($_product->variation_data)) :
								echo jigoshop_get_formatted_variation( $_product->variation_data );
							endif;

							echo '</td>';
							echo '<td>'.jigoshop_price($_product->get_price()).'</td>';
							echo '<td>'.$order_item['qty'].'</td>';

							echo '</tr>';

						endforeach;
						?>
					</tbody>
				</table>

				<div style="width: 49%; float:left;">
					<h2><?php _e('Billing Address', 'jigoshop'); ?></h2>
					<p><?php
					$address = $order->billing_first_name.' '.$order->billing_last_name.'<br/>';
					if ($order->billing_company) $address .= $order->billing_company.'<br/>';
					$address .= $order->formatted_billing_address;
					echo $address;
					?></p>
				</div>
				<div style="width: 49%; float:right;">
					<h2><?php _e('Shipping Address', 'jigoshop'); ?></h2>
					<p><?php
					$address = $order->shipping_first_name.' '.$order->shipping_last_name.'<br/>';
					if ($order->shipping_company) $address .= $order->shipping_company.'<br/>';
					$address .= $order->formatted_shipping_address;
					echo $address;
					?></p>
				</div>
				<div class="clear"></div>
				<?php

			else :
				echo '<p>'.__('Sorry, we could not find that order id in our database. <a href="'.get_permalink($post->ID).'">Want to retry?</a>', 'jigoshop').'</p>';
			endif;
		else :
			echo '<p>'.sprintf(__('Sorry, we could not find that order id in our database. <a href="%s">Want to retry?</a></p>', 'jigoshop'), get_permalink($post->ID));
		endif;

	else :

		?>
		<form action="<?php echo esc_url( get_permalink($post->ID) ); ?>" method="post" class="track_order">

			<p><?php _e('To track your order please enter your Order ID in the box below and press return. This was given to you on your receipt and in the confirmation email you should have received.', 'jigoshop'); ?></p>

			<p class="form-row form-row-first"><label for="orderid"><?php _e('Order ID', 'jigoshop'); ?></label> <input class="input-text" type="text" name="orderid" id="orderid" placeholder="<?php _e('Found in your order confirmation email.', 'jigoshop'); ?>" /></p>
			<p class="form-row form-row-last"><label for="order_email"><?php _e('Billing Email', 'jigoshop'); ?></label> <input class="input-text" type="text" name="order_email" id="order_email" placeholder="<?php _e('Email you used during checkout.', 'jigoshop'); ?>" /></p>
			<div class="clear"></div>
			<p class="form-row"><input type="submit" class="button" name="track" value="<?php _e('Track"', 'jigoshop'); ?>" /></p>
			<?php jigoshop::nonce_field('order_tracking') ?>
		</form>
		<?php

	endif;

}