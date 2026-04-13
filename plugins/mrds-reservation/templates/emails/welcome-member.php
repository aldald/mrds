<?php
/**
 * Vars attendues:
 * $first_name, $last_name, $username, $password, $login_url,
 * $site_name, $brand_color
 */
?>
<p>Bonjour <strong><?php echo esc_html($first_name); ?> <?php echo esc_html($last_name); ?></strong>,</p>

<p>Bienvenue chez <?php echo esc_html($site_name); ?> ! Votre compte a été créé avec succès.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 25px 0;">
  <tr>
    <td style="background-color: #f8f8f8; border-left: 4px solid <?php echo esc_attr($brand_color); ?>; padding: 20px;">
      <p style="margin: 0 0 10px 0; font-size: 14px;"><strong>Vos identifiants de connexion :</strong></p>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Email :</strong> <?php echo esc_html($email); ?></p>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Nom d'utilisateur :</strong> <?php echo esc_html($username); ?></p>
      <p style="margin: 5px 0; font-size: 14px;"><strong>Mot de passe :</strong> <?php echo esc_html($password); ?></p>
    </td>
  </tr>
</table>

<p><em>Nous vous conseillons de changer votre mot de passe après votre première connexion.</em></p>

<table cellpadding="0" cellspacing="0" border="0" style="margin: 30px auto;">
  <tr>
    <td align="center" bgcolor="<?php echo esc_attr($brand_color); ?>">
      <a href="<?php echo esc_url($login_url); ?>" style="display: inline-block; padding: 15px 30px; font-size: 14px; color: #ffffff; text-decoration: none; font-weight: bold;">
        Se connecter
      </a>
    </td>
  </tr>
</table>

<p>À très bientôt dans nos restaurants partenaires !</p>