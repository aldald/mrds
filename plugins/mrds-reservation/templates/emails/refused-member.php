<?php
/**
 * Vars attendues:
 * $first_name, $restaurant_name, $restaurant_address,
 * $date_label, $time, $guests, $occasion, $allergies, $preferences,
 * $site_name, $brand_color, $carnet_url
 */
?>
<p>Bonjour <strong><?php echo esc_html($first_name); ?></strong>,</p>

<p>Nous sommes désolés, mais votre demande de réservation n'a pas pu être acceptée par le restaurant.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 25px 0;">
  <tr>
    <td style="background-color: #f8f8f8; border-left: 4px solid #dc3545; padding: 20px;">
      <p style="margin: 5px 0; font-size: 14px;"><strong>Restaurant :</strong> <?php echo esc_html($restaurant_name); ?></p>
      <?php if (!empty($restaurant_address)) : ?>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Adresse :</strong> <?php echo esc_html($restaurant_address); ?></p>
      <?php endif; ?>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Date demandée :</strong> <?php echo esc_html($date_label); ?></p>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Heure :</strong> <?php echo esc_html($time); ?></p>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Nombre de convives :</strong> <?php echo esc_html($guests); ?></p>
      <?php if (!empty($remise)) : ?>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Remise :</strong> <?php echo esc_html($remise); ?></p>
      <?php endif; ?>
      <?php if (!empty($occasion)) : ?>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Occasion :</strong> <?php echo esc_html($occasion); ?></p>
      <?php endif; ?>
      <?php if (!empty($allergies)) : ?>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Allergies :</strong> <?php echo esc_html($allergies); ?></p>
      <?php endif; ?>
      <?php if (!empty($preferences)) : ?>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Demandes spéciales :</strong> <?php echo esc_html($preferences); ?></p>
      <?php endif; ?>
    </td>
  </tr>
</table>

<p>Cela peut être dû à une indisponibilité ce jour-là. N'hésitez pas à réserver dans l'un de nos autres restaurants partenaires !</p>

<table cellpadding="0" cellspacing="0" border="0" style="margin: 30px auto;">
  <tr>
    <td align="center" bgcolor="<?php echo esc_attr($brand_color); ?>">
      <a href="<?php echo esc_url($carnet_url); ?>" style="display: inline-block; padding: 15px 30px; font-size: 14px; color: #ffffff; text-decoration: none; font-weight: bold;">
        Découvrir nos restaurants
      </a>
    </td>
  </tr>
</table>

<p>À très bientôt,<br>L'équipe <?php echo esc_html($site_name); ?></p>