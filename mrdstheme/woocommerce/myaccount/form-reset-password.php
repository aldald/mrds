<?php
/**
 * Lost password reset form - MRDS Custom
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-reset-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_reset_password_form' );
?>

<style>
/* MRDS Reset Password Styles */
.mrds-form-wrapper {
	display: flex;
	justify-content: center;
	align-items: center;
	padding: 40px 20px;
	min-height: 50vh;
}

.mrds-form-container {
	background-color: #ffffff;
	border-radius: 0;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
	padding: 40px;
	width: 100%;
	max-width: 450px;
}

.mrds-form-container h2 {
	color: #141B42;
	font-size: 24px;
	font-weight: 600;
	margin: 0 0 20px;
	text-align: center;
}

.mrds-form-container .description {
	color: #636363;
	font-size: 14px;
	line-height: 1.6;
	margin-bottom: 25px;
	text-align: center;
}

.mrds-form-container .woocommerce-form-row {
	margin-bottom: 20px;
}

.mrds-form-container .form-row-first,
.mrds-form-container .form-row-last {
	width: 100%;
	float: none;
}

.mrds-form-container label {
	color: #141B42;
	font-size: 14px;
	font-weight: 500;
	display: block;
	margin-bottom: 8px;
}

.mrds-form-container .woocommerce-Input,
.mrds-form-container input[type="text"],
.mrds-form-container input[type="email"],
.mrds-form-container input[type="password"] {
	width: 100%;
	padding: 12px 15px;
	border: 1px solid #e5e5e5;
	border-radius: 0;
	font-size: 14px;
	transition: border-color 0.3s ease;
	box-sizing: border-box;
}

.mrds-form-container .woocommerce-Input:focus,
.mrds-form-container input[type="text"]:focus,
.mrds-form-container input[type="email"]:focus,
.mrds-form-container input[type="password"]:focus {
	outline: none;
	border-color: #DA9D42;
}

.mrds-form-container .woocommerce-Button,
.mrds-form-container button[type="submit"] {
	background-color: #DA9D42 !important;
	color: #ffffff !important;
	border: none;
	border-radius: 0;
	padding: 14px 30px;
	font-size: 14px;
	font-weight: 600;
	cursor: pointer;
	transition: background-color 0.3s ease;
	width: 100%;
	text-transform: none;
}

.mrds-form-container .woocommerce-Button:hover,
.mrds-form-container button[type="submit"]:hover {
	background-color: #c98c3a !important;
}

.mrds-form-container .form-row {
	margin-top: 25px;
}

.mrds-form-container .clear {
	clear: both;
}

/* Responsive */
@media screen and (max-width: 768px) {
	.mrds-form-container {
		padding: 30px 20px;
	}
}
</style>

<div class="mrds-form-wrapper">
	<div class="mrds-form-container">
		
		<h2><?php esc_html_e( 'Nouveau mot de passe', 'woocommerce' ); ?></h2>
		
		<p class="description"><?php echo apply_filters( 'woocommerce_reset_password_message', esc_html__( 'Veuillez saisir votre nouveau mot de passe ci-dessous.', 'woocommerce' ) ); ?></p>

		<form method="post" class="woocommerce-ResetPassword lost_reset_password">

			<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
				<label for="password_1"><?php esc_html_e( 'Nouveau mot de passe', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password_1" id="password_1" autocomplete="new-password" required aria-required="true" />
			</p>
			
			<p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
				<label for="password_2"><?php esc_html_e( 'Confirmer le mot de passe', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password_2" id="password_2" autocomplete="new-password" required aria-required="true" />
			</p>

			<input type="hidden" name="reset_key" value="<?php echo esc_attr( $args['key'] ); ?>" />
			<input type="hidden" name="reset_login" value="<?php echo esc_attr( $args['login'] ); ?>" />

			<div class="clear"></div>

			<?php do_action( 'woocommerce_resetpassword_form' ); ?>

			<p class="woocommerce-form-row form-row">
				<input type="hidden" name="wc_reset_password" value="true" />
				<button type="submit" class="woocommerce-Button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" value="<?php esc_attr_e( 'Enregistrer', 'woocommerce' ); ?>"><?php esc_html_e( 'Enregistrer', 'woocommerce' ); ?></button>
			</p>

			<?php wp_nonce_field( 'reset_password', 'woocommerce-reset-password-nonce' ); ?>

		</form>
		
	</div>
</div>

<?php
do_action( 'woocommerce_after_reset_password_form' );