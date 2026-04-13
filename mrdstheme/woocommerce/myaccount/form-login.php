<?php

/**
 * Login Form - MRDS Custom
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

do_action('woocommerce_before_customer_login_form'); ?>

<style>
	/* MRDS Custom Login Styles */
	.mrds-login-wrapper {
		display: flex;
		justify-content: center;
		align-items: center;
		padding: 40px 20px;
		min-height: 50vh;
	}

	.mrds-login-container {
		background-color: #ffffff;
		border-radius: 0;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
		padding: 40px;
		width: 100%;
		max-width: 450px;
	}

	.mrds-login-container h2 {
		color: #141B42;
		font-size: 24px;
		font-weight: 600;
		margin: 0 0 30px;
		text-align: center;
	}

	.mrds-login-container .woocommerce-form-row {
		margin-bottom: 20px;
	}
	input#password, input#username {
		border-radius: 0;
	}
	.mrds-login-container label {
		color: #141B42;
		font-size: 14px;
		font-weight: 500;
		display: block;
		margin-bottom: 8px;
	}

	.mrds-login-container .woocommerce-Input,
	.mrds-login-container input[type="text"],
	.mrds-login-container input[type="email"],
	.mrds-login-container input[type="password"] {
		width: 100%;
		padding: 12px 15px;
		border: 1px solid #e5e5e5;
		border-radius: 0;
		font-size: 14px;
		transition: border-color 0.3s ease;
		box-sizing: border-box;
	}

	.mrds-login-container .woocommerce-Input:focus,
	.mrds-login-container input[type="text"]:focus,
	.mrds-login-container input[type="email"]:focus,
	.mrds-login-container input[type="password"]:focus {
		outline: none;
		border-color: #DA9D42;
	}

	.mrds-login-container .form-row {

		gap: 15px;
		margin-top: 25px;
	}

	.mrds-login-container .woocommerce-form__label-for-checkbox {
		display: flex;
		align-items: center;
		gap: 8px;
		font-size: 14px;
		color: #636363;
		cursor: pointer;
	}

	.mrds-login-container .woocommerce-form__label-for-checkbox input[type="checkbox"] {
		width: 16px;
		height: 16px;
		accent-color: #DA9D42;
	}

	.mrds-login-container .woocommerce-button,
	.mrds-login-container button[type="submit"] {
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

	.mrds-login-container .woocommerce-button:hover,
	.mrds-login-container button[type="submit"]:hover {
		background-color: #c98c3a !important;
	}

	.mrds-login-container .woocommerce-LostPassword {
		text-align: center;
		margin-top: 20px;
	}

	.mrds-login-container .woocommerce-LostPassword a {
		color: #DA9D42;
		font-size: 14px;
		text-decoration: none;
		font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;
	}

	.mrds-login-container .woocommerce-LostPassword a:hover {
		text-decoration: underline;
	}

	/* Separator for Register section */
	.mrds-login-separator {
		display: flex;
		align-items: center;
		text-align: center;
		margin: 30px 0;
		color: #999;
		font-size: 13px;
	}

	.mrds-login-separator::before,
	.mrds-login-separator::after {
		content: '';
		flex: 1;
		border-bottom: 1px solid #e5e5e5;
	}

	.mrds-login-separator::before {
		margin-right: 15px;
	}

	.mrds-login-separator::after {
		margin-left: 15px;
	}

	/* Two columns layout if registration enabled */
	.mrds-login-wrapper.has-registration {
		align-items: flex-start;
	}

	.mrds-login-wrapper.has-registration .mrds-login-container {
		max-width: 900px;
	}

	.mrds-login-columns {
		display: flex;
		gap: 40px;
	}

	.mrds-login-column {
		flex: 1;
	}

	.mrds-login-column:first-child {
		border-right: 1px solid #e5e5e5;
		padding-right: 40px;
	}

	.mrds-login-column:last-child {
		padding-left: 0;
	}

	.woocommerce-account .woocommerce {
		display: block !important;
	}

	.mrds-login-container {

		width: 100%;
		max-width: 800px !important;
		margin: 0 auto;
	}

.woocommerce form.checkout_coupon, .woocommerce form.login, .woocommerce form.register {

    border-radius: 0 !important;
}
	/* Responsive */
	@media screen and (max-width: 768px) {
		.mrds-login-columns {
			flex-direction: column;
			gap: 30px;
		}

		.mrds-login-column:first-child {
			border-right: none;
			border-bottom: 1px solid #e5e5e5;
			padding-right: 0;
			padding-bottom: 30px;
		}

		.mrds-login-container {
			padding: 30px 20px;
		}
	}
</style>

<?php if ('yes' === get_option('woocommerce_enable_myaccount_registration')) : ?>

	<div class="mrds-login-wrapper has-registration">
		<div class="mrds-login-container">
			<div class="mrds-login-columns">

				<!-- Colonne Connexion -->
				<div class="mrds-login-column">
					<h2><?php esc_html_e('Se connecter', 'woocommerce'); ?></h2>

					<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>

						<?php do_action('woocommerce_login_form_start'); ?>

						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="username"><?php esc_html_e('Identifiant ou e-mail', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
							<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo (! empty($_POST['username']) && is_string($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" required aria-required="true" />
						</p>

						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="password"><?php esc_html_e('Mot de passe', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
							<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
						</p>

						<?php do_action('woocommerce_login_form'); ?>

						<p class="form-row">
							<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
								<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e('Se souvenir de moi', 'woocommerce'); ?></span>
							</label>
							<?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
							<button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="login" value="<?php esc_attr_e('Se connecter', 'woocommerce'); ?>"><?php esc_html_e('Se connecter', 'woocommerce'); ?></button>
						</p>

						<p class="woocommerce-LostPassword lost_password">
							<a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Mot de passe perdu ?', 'woocommerce'); ?></a>
						</p>

						<?php do_action('woocommerce_login_form_end'); ?>

					</form>
				</div>

				<!-- Colonne Inscription -->
				<div class="mrds-login-column">
					<h2><?php esc_html_e('Créer un compte', 'woocommerce'); ?></h2>

					<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action('woocommerce_register_form_tag'); ?>>

						<?php do_action('woocommerce_register_form_start'); ?>

						<?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>

							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="reg_username"><?php esc_html_e('Nom d\'utilisateur', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
								<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo (! empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" required aria-required="true" />
							</p>

						<?php endif; ?>

						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="reg_email"><?php esc_html_e('Adresse e-mail', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
							<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo (! empty($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>" required aria-required="true" />
						</p>

						<?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>

							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="reg_password"><?php esc_html_e('Mot de passe', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
								<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
							</p>

						<?php else : ?>

							<p style="color: #636363; font-size: 13px; margin-bottom: 20px;"><?php esc_html_e('Un lien pour définir votre mot de passe sera envoyé à votre adresse e-mail.', 'woocommerce'); ?></p>

						<?php endif; ?>

						<?php do_action('woocommerce_register_form'); ?>

						<p class="woocommerce-form-row form-row">
							<?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
							<button type="submit" class="woocommerce-Button woocommerce-button button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?> woocommerce-form-register__submit" name="register" value="<?php esc_attr_e('S\'inscrire', 'woocommerce'); ?>"><?php esc_html_e('S\'inscrire', 'woocommerce'); ?></button>
						</p>

						<?php do_action('woocommerce_register_form_end'); ?>

					</form>
				</div>

			</div>
		</div>
	</div>

<?php else : ?>

	<!-- Version sans inscription -->
	<div class="mrds-login-wrapper">
		<div class="mrds-login-container">
			<h2><?php esc_html_e('Se connecter', 'woocommerce'); ?></h2>

			<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>

				<?php do_action('woocommerce_login_form_start'); ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="username"><?php esc_html_e('Identifiant ou e-mail', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo (! empty($_POST['username']) && is_string($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" required aria-required="true" />
				</p>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="password"><?php esc_html_e('Mot de passe', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
					<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
				</p>

				<?php do_action('woocommerce_login_form'); ?>

				<p class="form-row">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e('Se souvenir de moi', 'woocommerce'); ?></span>
					</label>
					<?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
					<button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="login" value="<?php esc_attr_e('Se connecter', 'woocommerce'); ?>"><?php esc_html_e('Se connecter', 'woocommerce'); ?></button>
				</p>

				<p class="woocommerce-LostPassword lost_password">
					<a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Mot de passe perdu ?', 'woocommerce'); ?></a>
				</p>

				<?php do_action('woocommerce_login_form_end'); ?>

			</form>
		</div>
	</div>

<?php endif; ?>

<?php do_action('woocommerce_after_customer_login_form'); ?>