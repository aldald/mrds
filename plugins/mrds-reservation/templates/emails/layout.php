<?php
/**
 * Layout global email MRDS.
 * Vars dispo: $content, $site_name, $support_email, $header_image, $brand_color, $footer_country
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin: 0; padding: 0; background-color: #f7f7f7; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
  <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f7f7f7;">
    <tr>
      <td align="center" style="padding: 40px 20px;">
        <table cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 4px; overflow: hidden;">

          <!-- Header Logo - Image pleine largeur -->
          <?php if (!empty($header_image)) : ?>
          <tr>
            <td align="center" style="padding: 0; margin: 0; line-height: 0;">
              <img src="<?php echo esc_url($header_image); ?>"
                   alt="<?php echo esc_attr($site_name); ?>"
                   width="600"
                   style="width: 100%; height: auto; display: block;">
            </td>
          </tr>
          <?php endif; ?>

          <!-- Contenu -->
          <tr>
            <td style="padding: 40px; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #636363;">
              <?php echo $content; ?>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color: #3c3c3c; padding: 30px 40px; text-align: center;">
              <p style="margin: 0 0 15px 0; font-size: 13px; color: #b8b8b8;">
                Encore merci ! Contactez-nous à l'adresse
                <a href="mailto:<?php echo esc_attr($support_email); ?>" style="color: <?php echo esc_attr($brand_color); ?>; text-decoration: none;">
                  <?php echo esc_html($support_email); ?>
                </a>,
                si vous avez besoin d'aide avec votre réservation.
              </p>

              <p style="margin: 0; font-size: 13px; color: #b8b8b8;">
                <strong style="color: #ffffff;"><?php echo esc_html($site_name); ?></strong><br>
                <?php echo esc_html($footer_country); ?>
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>