<?php
/**
 * "Order received" message - MRDS Custom
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/order-received.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.8.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
?>

<style>
/* MRDS Order Received Styles */
.mrds-order-received-wrapper {
	display: flex;
	justify-content: center;
	align-items: center;
	padding: 40px 20px;
}

.mrds-order-received-container {
	background-color: #ffffff;
	border-radius: 0;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
	padding: 40px;
	width: 100%;
	max-width: 500px;
	text-align: center;
}

.mrds-order-received-container h2 {
	color: #141B42;
	font-size: 24px;
	font-weight: 600;
	margin: 0 0 15px;
}

.mrds-order-received-container .message-text {
	color: #636363;
	font-size: 14px;
	line-height: 1.7;
	margin: 0;
}

.mrds-order-received-container .woocommerce-notice {
	background: none;
	border: none;
	padding: 0;
	margin: 0;
	color: #636363;
	font-size: 14px;
	line-height: 1.7;
}

.mrds-order-received-container .woocommerce-notice::before {
	display: none;
}

/* Woocommerce Info */
.woocommerce-info {
	border-top-color: #141B42 !important;
}

/* Forms */
.woocommerce form.checkout_coupon,
.woocommerce form.login,
.woocommerce form.register {
	background: #fff !important;
	border-radius: 0 !important;
}

/* Boutons */
.woocommerce-order-received .woocommerce-Button,
.woocommerce-order-received .button,
.woocommerce-order-received button[type="submit"],
.woocommerce-order-received input[type="submit"],
.woocommerce-order-received a.button {
	background-color: #DA9D42 !important;
	color: #ffffff !important;
	border: none !important;
	border-radius: 0 !important;
	padding: 14px 30px !important;
	font-size: 14px !important;
	font-weight: 600 !important;
	cursor: pointer;
	transition: background-color 0.3s ease;
	text-transform: none !important;
	text-decoration: none !important;
}

.woocommerce-order-received .woocommerce-Button:hover,
.woocommerce-order-received .button:hover,
.woocommerce-order-received button[type="submit"]:hover,
.woocommerce-order-received input[type="submit"]:hover,
.woocommerce-order-received a.button:hover {
	background-color: #c98c3a !important;
	color: #ffffff !important;
}

/* Responsive */
@media screen and (max-width: 768px) {
	.mrds-order-received-container {
		padding: 30px 20px;
	}
}
</style>

<div class="mrds-order-received-wrapper">
	<div class="mrds-order-received-container">

		<h2><?php esc_html_e( 'Merci pour votre commande !', 'woocommerce' ); ?></h2>

		<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received message-text">
			<?php
			/**
			 * Filter the message shown after a checkout is complete.
			 *
			 * @since 2.2.0
			 *
			 * @param string         $message The message.
			 * @param WC_Order|false $order   The order created during checkout, or false if order data is not available.
			 */
			$message = apply_filters(
				'woocommerce_thankyou_order_received_text',
				esc_html( __( 'Votre commande a bien été enregistrée. Vous recevrez un e-mail de confirmation sous peu.', 'woocommerce' ) ),
				$order
			);

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $message;
			?>
		</p>
		
	</div>
</div>