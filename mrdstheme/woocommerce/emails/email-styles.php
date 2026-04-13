<?php
/**
 * Email Styles - MRDS Custom
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-styles.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 9.9.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

// =============================================
// MRDS CUSTOM COLORS
// =============================================
$bg          = '#f7f7f7';  // Background général
$body        = '#ffffff';  // Background du contenu
$base        = '#DA9D42';  // Couleur principale (boutons, liens)
$text        = '#636363';  // Couleur du texte
$footer_bg   = '#3c3c3c';  // Background footer
$footer_text = '#b8b8b8';  // Texte footer
$heading     = '#141B42';  // Couleur des titres
$border      = '#e5e5e5';  // Couleur des bordures

// Font family
$safe_font_family = "'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif";

$base_text       = '#ffffff';
$link_color      = $base;
$text_lighter_20 = '#636363';

?>
/* =============================================
   MRDS EMAIL STYLES
   ============================================= */

body {
	background-color: <?php echo esc_attr( $bg ); ?>;
	padding: 0;
	margin: 0;
	text-align: center;
	font-family: <?php echo $safe_font_family; ?>;
	-webkit-font-smoothing: antialiased;
}

#outer_wrapper {
	background-color: <?php echo esc_attr( $bg ); ?>;
}

#inner_wrapper {
	background-color: <?php echo esc_attr( $body ); ?>;
	border-radius: 4px;
	overflow: hidden;
	box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

#wrapper {
	margin: 0 auto;
	padding: 40px 0;
	-webkit-text-size-adjust: none !important;
	width: 100%;
	max-width: 600px;
}

#template_container {
	background-color: <?php echo esc_attr( $body ); ?>;
	border: 0;
	border-radius: 0;
	box-shadow: none;
}

/* =============================================
   HEADER
   ============================================= */

#template_header_image {
	padding: 0 !important;
	margin: 0 !important;
	line-height: 0 !important;
}

#template_header_image img {
	width: 100% !important;
	height: auto !important;
	display: block !important;
	border: 0;
}

#template_header_image p {
	margin: 0;
	padding: 0;
	line-height: 0;
}

#template_header {
	background-color: <?php echo esc_attr( $body ); ?>;
	border-bottom: 1px solid <?php echo esc_attr( $border ); ?>;
	border-radius: 0;
}

#template_header h1,
#template_header h1 a {
	color: <?php echo esc_attr( $heading ); ?>;
	background-color: transparent;
}

#header_wrapper {
	padding: 25px 40px;
	display: block;
}

#header_wrapper h1 {
	text-align: left;
	font-size: 24px;
	font-weight: 600;
	line-height: 1.2;
	margin: 0;
}

/* =============================================
   BODY CONTENT
   ============================================= */

#template_body {
	background-color: <?php echo esc_attr( $body ); ?>;
}

#body_content {
	background-color: <?php echo esc_attr( $body ); ?>;
}

#body_content table td {
	padding: 30px 40px;
}

#body_content table td td {
	padding: 12px;
}

#body_content table td th {
	padding: 12px;
}

#body_content p {
	margin: 0 0 16px;
}

#body_content_inner {
	color: <?php echo esc_attr( $text ); ?>;
	font-family: <?php echo $safe_font_family; ?>;
	font-size: 14px;
	line-height: 1.6;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

/* =============================================
   TYPOGRAPHY
   ============================================= */

h1 {
	color: <?php echo esc_attr( $heading ); ?>;
	font-family: <?php echo $safe_font_family; ?>;
	font-size: 24px;
	font-weight: 600;
	line-height: 1.2;
	margin: 0 0 20px;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

h2 {
	color: <?php echo esc_attr( $heading ); ?>;
	display: block;
	font-family: <?php echo $safe_font_family; ?>;
	font-size: 20px;
	font-weight: bold;
	line-height: 1.4;
	margin: 0 0 18px;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

h3 {
	color: <?php echo esc_attr( $heading ); ?>;
	display: block;
	font-family: <?php echo $safe_font_family; ?>;
	font-size: 16px;
	font-weight: bold;
	line-height: 1.4;
	margin: 16px 0 8px;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

a {
	color: <?php echo esc_attr( $link_color ); ?>;
	font-weight: normal;
	text-decoration: none;
}

a:hover {
	text-decoration: underline;
}

p {
	font-family: <?php echo $safe_font_family; ?>;
	font-size: 14px;
	line-height: 1.6;
	color: <?php echo esc_attr( $text ); ?>;
	margin: 0 0 16px;
}

/* =============================================
   TABLES & DATA
   ============================================= */

.td {
	color: <?php echo esc_attr( $text ); ?>;
	border: 1px solid <?php echo esc_attr( $border ); ?>;
	vertical-align: middle;
	padding: 12px;
	font-family: <?php echo $safe_font_family; ?>;
	font-size: 14px;
}

table.td {
	border-collapse: collapse;
	width: 100%;
}

.td thead th {
	background-color: <?php echo esc_attr( $base ); ?>;
	color: #ffffff;
	font-weight: 600;
	text-align: left;
	padding: 12px;
	border: 1px solid <?php echo esc_attr( $base ); ?>;
}

.td tbody td {
	background-color: #ffffff;
	text-align: left;
	vertical-align: middle;
	padding: 12px;
}

.td tfoot td,
.td tfoot th {
	background-color: #f8f8f8;
	font-weight: 600;
}

/* =============================================
   ORDER DETAILS
   ============================================= */

#body_content table .email-order-details td,
#body_content table .email-order-details th {
	padding: 8px 12px;
}

#body_content .email-order-details tbody tr:last-child td {
	border-bottom: 1px solid <?php echo esc_attr( $border ); ?>;
	padding-bottom: 24px;
}

#body_content .email-order-details tfoot tr:first-child td,
#body_content .email-order-details tfoot tr:first-child th {
	padding-top: 24px;
}

#body_content .email-order-details .order-totals td,
#body_content .email-order-details .order-totals th {
	font-weight: normal;
	padding-bottom: 5px;
	padding-top: 5px;
}

#body_content .email-order-details .order-totals-total th {
	font-weight: bold;
}

#body_content .email-order-details .order-totals-total td {
	font-weight: bold;
	font-size: 18px;
	color: <?php echo esc_attr( $heading ); ?>;
}

.order_item {
	border-bottom: 1px solid <?php echo esc_attr( $border ); ?>;
}

.order_item td {
	padding: 15px 12px;
}

/* =============================================
   ADDRESS
   ============================================= */

.address {
	color: <?php echo esc_attr( $text ); ?>;
	font-style: normal;
	padding: 15px;
	background-color: #f8f8f8;
	border-left: 4px solid <?php echo esc_attr( $base ); ?>;
	margin: 0 0 20px;
	word-break: break-all;
}

.address-title {
	color: <?php echo esc_attr( $heading ); ?>;
	font-family: <?php echo $safe_font_family; ?>;
	font-weight: bold;
}

/* =============================================
   BUTTONS
   ============================================= */

.button,
.wc-button,
.btn,
a.button {
	display: inline-block;
	background-color: <?php echo esc_attr( $base ); ?> !important;
	color: #ffffff !important;
	padding: 15px 30px;
	font-family: <?php echo $safe_font_family; ?>;
	font-size: 14px;
	font-weight: bold;
	text-decoration: none !important;
	border-radius: 4px;
	border: none;
	text-align: center;
}

.button:hover,
a.button:hover {
	background-color: #c98c3a !important;
}

/* =============================================
   INFO BOXES
   ============================================= */

.info-box {
	background-color: #f8f8f8;
	border-left: 4px solid <?php echo esc_attr( $base ); ?>;
	padding: 20px;
	margin: 25px 0;
}

/* =============================================
   FOOTER
   ============================================= */

#template_footer {
	background-color: <?php echo esc_attr( $footer_bg ); ?> !important;
	border-radius: 0;
}

#template_footer td {
	padding: 0;
	border-radius: 0;
}

#template_footer #credit {
	border: 0;
	color: <?php echo esc_attr( $footer_text ); ?>;
	font-family: <?php echo $safe_font_family; ?>;
	font-size: 13px;
	line-height: 1.6;
	text-align: center;
	padding: 30px 40px;
}

#template_footer #credit p {
	margin: 0 0 15px;
	color: <?php echo esc_attr( $footer_text ); ?>;
	font-size: 13px;
}

#template_footer #credit a {
	color: <?php echo esc_attr( $base ); ?> !important;
	text-decoration: none;
}

#template_footer #credit a:hover {
	text-decoration: underline;
}

/* =============================================
   IMAGES
   ============================================= */

img {
	border: none;
	display: inline-block;
	font-size: 14px;
	font-weight: bold;
	height: auto;
	outline: none;
	text-decoration: none;
	text-transform: capitalize;
	vertical-align: middle;
	max-width: 100%;
}

/* =============================================
   UTILITIES
   ============================================= */

.text {
	color: <?php echo esc_attr( $text ); ?>;
	font-family: <?php echo $safe_font_family; ?>;
}

.link {
	color: <?php echo esc_attr( $link_color ); ?>;
}

.font-family {
	font-family: <?php echo $safe_font_family; ?>;
}

.text-align-left {
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

.text-align-right {
	text-align: <?php echo is_rtl() ? 'left' : 'right'; ?>;
}

.hr {
	border-bottom: 1px solid <?php echo esc_attr( $border ); ?>;
	margin: 16px 0;
}

/* =============================================
   RESPONSIVE
   ============================================= */

@media screen and (max-width: 600px) {
	#wrapper {
		padding: 20px 10px !important;
	}
	
	#template_header_image img {
		width: 100% !important;
	}
	
	#header_wrapper {
		padding: 20px !important;
	}
	
	#header_wrapper h1 {
		font-size: 20px !important;
	}
	
	#body_content table > tbody > tr > td,
	#body_content_inner_cell {
		padding: 20px !important;
	}
	
	#body_content_inner {
		font-size: 13px !important;
	}
	
	.td thead th,
	.td tbody td,
	.td tfoot td {
		padding: 10px 8px !important;
		font-size: 12px !important;
	}
	
	#template_footer #credit {
		padding: 20px !important;
	}
	
	.address {
		padding: 12px !important;
	}
}
<?php