<?php
/**
 * Vars attendues:
 * $restaurant_name, $restaurant_address, $remise,
 * $client_name, $phone, $email, $date_label, $time, $guests,
 * $occasion, $allergies, $preferences, $edit_link, $site_name, $brand_color
 */
?>
<h2 style="margin: 0 0 20px 0; font-size: 18px; color: #141B42;">Nouvelle demande de réservation</h2>

<p>Bonjour,</p>
<p>Une nouvelle demande de réservation a été effectuée pour <strong><?php echo esc_html($restaurant_name); ?></strong></p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 25px 0;">
  <tr>
    <td style="background-color: #f8f8f8; border-left: 4px solid <?php echo esc_attr($brand_color); ?>; padding: 20px;">
      <p style="margin: 5px 0; font-size: 14px;"><strong>Client :</strong> <?php echo esc_html($client_name); ?></p>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Téléphone :</strong> <?php echo esc_html($phone); ?></p>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Email :</strong> <?php echo esc_html($email); ?></p>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Date :</strong> <?php echo esc_html($date_label); ?></p>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Heure :</strong> <?php echo esc_html($time); ?></p>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Nombre de convives :</strong> <?php echo esc_html($guests); ?></p>
      <?php if (!empty($remise)) : ?>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Remise accordée :</strong> <?php echo esc_html($remise); ?></p>
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

<p style="text-align: center;"><em>Ce client est adhérent de <?php echo esc_html($site_name); ?>.</em></p>

<table cellpadding="0" cellspacing="0" border="0" style="margin: 30px auto;">
  <tr>
    <td align="center" bgcolor="<?php echo esc_attr($brand_color); ?>">
      <a href="<?php echo esc_url($edit_link); ?>" style="display: inline-block; padding: 15px 30px; font-size: 14px; color: #ffffff; text-decoration: none; font-weight: bold;">
        Voir et gérer cette réservation
      </a>
    </td>
  </tr>
</table>

<p style="font-size: 12px; color: #999; text-align: center;">
  Connectez-vous à votre compte pour <strong>confirmer</strong> ou <strong>refuser</strong> cette réservation.<br>
  Un email sera alors envoyé au client.
</p>