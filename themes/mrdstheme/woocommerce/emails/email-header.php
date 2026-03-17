<?php
/**
 * Email Header - MRDS Custom
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-header.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.0.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$store_name                 = $store_name ?? get_bloginfo( 'name', 'display' );

// MRDS Custom Header Image
$mrds_header_image = 'http://mesrondsdeserviette.intbase.com/wp-content/uploads/2026/01/header-email.png';

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<title><?php echo esc_html( $store_name ); ?></title>
	</head>
	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="margin: 0; padding: 0; background-color: #f7f7f7;">
		<table width="100%" id="outer_wrapper" style="background-color: #f7f7f7;">
			<tr>
				<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
				<td width="600">
					<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>" style="margin: 0 auto; padding: 40px 0; max-width: 600px;">
						<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="inner_wrapper" style="background-color: #ffffff; border-radius: 4px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
							<tr>
								<td align="center" valign="top">
									<!-- MRDS Header Image - Pleine largeur -->
									<table border="0" cellpadding="0" cellspacing="0" width="100%">
										<tr>
											<td id="template_header_image" style="padding: 0; margin: 0; line-height: 0;">
												<img src="<?php echo esc_url( $mrds_header_image ); ?>" alt="<?php echo esc_attr( $store_name ); ?>" width="600" style="width: 100%; height: auto; display: block; border: 0;" />
											</td>
										</tr>
									</table>
									<!-- End MRDS Header -->
									
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" style="background-color: #ffffff;">
										<tr>
											<td align="center" valign="top">
												<!-- Header Title -->
												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" style="background-color: #ffffff; border-bottom: 1px solid #e5e5e5;">
													<tr>
														<td id="header_wrapper" style="padding: 25px 40px; display: block;">
															<h1 style="color: #141B42; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 24px; font-weight: 600; line-height: 1.2; margin: 0; text-align: left;"><?php echo esc_html( $email_heading ); ?></h1>
														</td>
													</tr>
												</table>
												<!-- End Header Title -->
											</td>
										</tr>
										<tr>
											<td align="center" valign="top">
												<!-- Body -->
												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body">
													<tr>
														<td valign="top" id="body_content" style="background-color: #ffffff;">
															<!-- Content -->
															<table border="0" cellpadding="20" cellspacing="0" width="100%">
																<tr>
																	<td valign="top" id="body_content_inner_cell" style="padding: 30px 40px;">
																		<div id="body_content_inner" style="color: #636363; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 14px; line-height: 1.6; text-align: left;">