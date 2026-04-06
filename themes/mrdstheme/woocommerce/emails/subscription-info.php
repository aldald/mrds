<?php

/**
 * Subscription information template - MRDS Custom
 *
 * @package WooCommerce_Subscriptions/Templates/Emails
 * @version 7.2.0
 */
if (! defined('ABSPATH')) {
    exit;
}

if (empty($subscriptions)) {
    return;
}

$has_automatic_renewal = false;
$is_parent_order       = wcs_order_contains_subscription($order, 'parent');
?>
<div style="margin-bottom: 40px;">
    <h2><?php esc_html_e('Informations sur l\'adhésion', 'woocommerce-subscriptions'); ?></h2>
    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 0.5em;" border="1">
        <thead>
            <tr>
                <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('Adhésion', 'woocommerce-subscriptions'); ?></th>
                <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('Date de début', 'woocommerce-subscriptions'); ?></th>
                <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('Fin d\'adhésion', 'woocommerce-subscriptions'); ?></th>
                <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('Total/an', 'woocommerce-subscriptions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subscriptions as $subscription) : ?>
                <?php $has_automatic_renewal = $has_automatic_renewal || ! $subscription->is_manual(); ?>
                <tr>
                    <td class="td" scope="row" style="text-align:left;">
                        <a href="<?php echo esc_url(($is_admin_email) ? wcs_get_edit_post_link($subscription->get_id()) : $subscription->get_view_order_url()); ?>">
                            <?php echo sprintf(esc_html_x('#%s', 'subscription number in email table. (eg: #106)', 'woocommerce-subscriptions'), esc_html($subscription->get_order_number())); ?>
                        </a>
                    </td>
                    <td class="td" scope="row" style="text-align:left;">
                        <?php echo esc_html(date_i18n(wc_date_format(), $subscription->get_time('start_date', 'site'))); ?>
                    </td>
                    <td class="td" scope="row" style="text-align:left;">
                        <?php
                        $end_time     = $subscription->get_time('end', 'site');
                        $next_payment = $subscription->get_time('next_payment', 'site');
                        $display_date = $end_time > 0 ? $end_time : $next_payment;
                        echo esc_html($display_date > 0 ? date_i18n(wc_date_format(), $display_date) : '—');
                        ?>
                    </td>
                    <td class="td" scope="row" style="text-align:left;">
                        <?php echo wp_kses_post(wc_price($subscription->get_total()) . ' TTC'); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>