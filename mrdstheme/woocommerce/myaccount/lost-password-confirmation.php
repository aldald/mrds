<?php
/**
 * Lost password confirmation text - MRDS Custom
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/lost-password-confirmation.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.9.0
 */

defined( 'ABSPATH' ) || exit;
?>

<style>
/* MRDS Confirmation Styles */
.mrds-confirmation-wrapper {
	display: flex;
	justify-content: center;
	align-items: center;
	padding: 40px 20px;
	min-height: 50vh;
}

.mrds-confirmation-container {
	background-color: #ffffff;
	border-radius: 0;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
	padding: 40px;
	width: 100%;
	max-width: 500px;
	text-align: center;
}

.mrds-confirmation-container h2 {
	color: #141B42;
	font-size: 24px;
	font-weight: 600;
	margin: 0 0 20px;
}

.mrds-confirmation-icon {
	width: 70px;
	height: 70px;
	background-color: #D4EDDA;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 auto 25px;
}

.mrds-confirmation-icon svg {
	width: 35px;
	height: 35px;
	stroke: #28a745;
}

.mrds-confirmation-container .message-text {
	color: #636363;
	font-size: 14px;
	line-height: 1.7;
	margin: 0 0 30px;
}

.mrds-confirmation-container .woocommerce-message {
	display: none;
}

.mrds-back-link {
	margin-top: 25px;
}

.mrds-back-link a {
	display: inline-block;
	background-color: #DA9D42;
	color: #ffffff !important;
	border: none;
	border-radius: 0;
	padding: 14px 30px;
	font-size: 14px;
	font-weight: 600;
	cursor: pointer;
	transition: background-color 0.3s ease;
	text-decoration: none;
}

.mrds-back-link a:hover {
	background-color: #c98c3a;
	text-decoration: none;
}

/* Responsive */
@media screen and (max-width: 768px) {
	.mrds-confirmation-container {
		padding: 30px 20px;
	}
}
</style>

<div class="mrds-confirmation-wrapper">
	<div class="mrds-confirmation-container">
		
		<!-- Icône de succès -->
		<div class="mrds-confirmation-icon">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
			</svg>
		</div>
		
		<h2><?php esc_html_e( 'E-mail envoyé !', 'woocommerce' ); ?></h2>

		<?php do_action( 'woocommerce_before_lost_password_confirmation_message' ); ?>

		<p class="message-text"><?php echo esc_html( apply_filters( 'woocommerce_lost_password_confirmation_message', esc_html__( 'Un e-mail de réinitialisation de mot de passe a été envoyé à l\'adresse associée à votre compte. Il peut prendre quelques minutes avant d\'arriver dans votre boîte de réception. Veuillez patienter au moins 10 minutes avant de réessayer.', 'woocommerce' ) ) ); ?></p>

		<?php do_action( 'woocommerce_after_lost_password_confirmation_message' ); ?>
		
		<p class="mrds-back-link">
			<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Retour à la connexion', 'woocommerce' ); ?></a>
		</p>
		
	</div>
</div>