<?php

/**
 * Email Footer - MRDS Custom
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-footer.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.0.0
 */

defined('ABSPATH') || exit;

$email = $email ?? null;

// MRDS Custom Footer Settings
$mrds_contact_email = get_option('mrds_email_support_email', 'contact@mesrondsdeserviette.com');
$mrds_site_name     = 'Mes ronds de serviette';
$mrds_country       = 'France';

?>
</div>
</td>
</tr>
</table>
<!-- End Content -->
</td>
</tr>
</table>
<!-- End Body -->
</td>
</tr>
</table>
</td>
</tr>
<tr>
	<td align="center" valign="top">
		<!-- MRDS Custom Footer -->
		<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_footer" style="background-color: #3c3c3c;">
			<tr>
				<td valign="top" style="padding: 30px 40px;">
					<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td valign="middle" id="credit" style="text-align: center; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 13px; line-height: 1.6; color: #b8b8b8;">
								<p style="margin: 0 0 15px 0; color: #b8b8b8;">
									Contactez-nous à l'adresse <a href="mailto:<?php echo esc_attr($mrds_contact_email); ?>" style="color: #DA9D42; text-decoration: none;"><?php echo esc_html($mrds_contact_email); ?></a>, si vous avez besoin d'aide concernant votre adhésion.
								</p>
								<p style="margin: 0; color: #b8b8b8;">
									<strong style="color: #ffffff;"><?php echo esc_html($mrds_site_name); ?></strong><br>
									<?php echo esc_html($mrds_country); ?>
								</p>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<!-- End MRDS Footer -->
	</td>
</tr>
</table>
</div>
</td>
<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
</tr>
</table>
</body>

</html>