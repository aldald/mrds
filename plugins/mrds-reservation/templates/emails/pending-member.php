<?php
/**
 * Vars attendues:
 * $first_name, $restaurant_name, $restaurant_address, $remise,
 * $date_label, $time, $guests, $occasion, $allergies, $preferences,
 * $site_name, $brand_color
 */
?>
<p>Bonjour <strong><?php echo esc_html($first_name); ?></strong>,</p>

<p>Votre demande de réservation a bien été enregistrée et est <strong>en attente de confirmation</strong> par le restaurant.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 25px 0;">
  <tr>
    <td style="background-color: #f8f8f8; border-left: 4px solid <?php echo esc_attr($brand_color); ?>; padding: 20px;">
      <p style="margin: 5px 0; font-size: 14px;"><strong>Restaurant :</strong> <?php echo esc_html($restaurant_name); ?></p>
      <?php if (!empty($restaurant_address)) : ?>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Adresse :</strong> <?php echo esc_html($restaurant_address); ?></p>
      <?php endif; ?>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Date :</strong> <?php echo esc_html($date_label); ?></p>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Heure :</strong> <?php echo esc_html($time); ?></p>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Nombre de personnes :</strong> <?php echo esc_html($guests); ?></p>
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

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 25px 0;">
  <tr>
    <td style="background-color: #FFF3CD; border: 1px solid #FFE69C; padding: 15px 20px; border-radius: 4px;">
      <p style="margin: 0; font-size: 14px; color: #856404;">
        <strong>En attente de confirmation</strong><br>
        Le restaurant va examiner votre demande et vous recevrez un email de confirmation ou de refus très prochainement.
      </p>
    </td>
  </tr>
</table>

<p>À très bientôt,<br>L'équipe <?php echo esc_html($site_name); ?></p>