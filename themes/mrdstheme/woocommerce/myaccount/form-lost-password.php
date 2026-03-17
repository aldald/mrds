<?php

/**
 * Lost password form - MRDS Custom
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-lost-password.php.
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

defined('ABSPATH') || exit;

do_action('woocommerce_before_lost_password_form');
?>

<style>
	/* MRDS Lost Password Styles */
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

	input#user_login {
		border-radius: 0;
	}

	.mentions-content p {
		text-align: left;
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
		width: auto;
		text-transform: none;
	}

	.mrds-form-container .woocommerce-Button:hover,
	.mrds-form-container button[type="submit"]:hover {
		background-color: #c98c3a !important;
	}

	.mrds-form-container .form-row {
		margin-top: 25px;
	}

	.mrds-back-link {
		text-align: center;
		margin-top: 20px;
	}

	.mrds-back-link a {
		color: #DA9D42;
		font-size: 14px;
		text-decoration: none;
	}

	.mrds-back-link a:hover {
		text-decoration: underline;
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

		<h2><?php esc_html_e('Mot de passe perdu', 'woocommerce'); ?></h2>

		<p class="description"><?php echo apply_filters('woocommerce_lost_password_message', esc_html__('Mot de passe perdu ? Veuillez saisir votre identifiant ou votre adresse e-mail. Vous recevrez un lien par e-mail pour créer un nouveau mot de passe.', 'woocommerce')); ?></p>

		<form method="post" class="woocommerce-ResetPassword lost_reset_password">

			<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
				<label for="user_login"><?php esc_html_e('Identifiant ou e-mail', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="text" name="user_login" id="user_login" autocomplete="username" required aria-required="true" />
			</p>

			<div class="clear"></div>

			<?php do_action('woocommerce_lostpassword_form'); ?>

			<p class="woocommerce-form-row form-row">
				<input type="hidden" name="wc_reset_password" value="true" />
				<button type="submit" class="woocommerce-Button button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" value="<?php esc_attr_e('Réinitialiser le mot de passe', 'woocommerce'); ?>"><?php esc_html_e('Réinitialiser le mot de passe', 'woocommerce'); ?></button>
			</p>

			<?php wp_nonce_field('lost_password', 'woocommerce-lost-password-nonce'); ?>

		</form>

		<p class="mrds-back-link">
			<a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">&larr; <?php esc_html_e('Retour à la connexion', 'woocommerce'); ?></a>
		</p>

	</div>
</div>

<?php
do_action('woocommerce_after_lost_password_form');
