<?php
/**
 * JVSTORE - Footer Global
 */
$siteName = getSiteConfig('site_name', SITE_NAME);
$whatsapp = getSiteConfig('whatsapp', WHATSAPP_NUMBER);
$email    = getSiteConfig('email_contacto', 'contacto@jvstore.com');
$fb       = getSiteConfig('facebook', '');
$ig       = getSiteConfig('instagram', '');
$tk       = getSiteConfig('tiktok', '');
?>
</main>

<!-- TRUST BAR -->
<section class="jv-trust">
  <div class="container">
    <div class="jv-trust-grid">
      <div class="jv-trust-item"><i class="fas fa-shipping-fast"></i><div><h4>Envío Rápido</h4><p>Despacho inmediato a todo el país</p></div></div>
      <div class="jv-trust-item"><i class="fas fa-shield-alt"></i><div><h4>Compra Segura</h4><p>Productos garantizados y certificados</p></div></div>
      <div class="jv-trust-item"><i class="fas fa-headset"></i><div><h4>Soporte 24/7</h4><p>Atención personalizada siempre</p></div></div>
      <div class="jv-trust-item"><i class="fas fa-undo-alt"></i><div><h4>Devoluciones</h4><p>Política de cambios sin complicaciones</p></div></div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="jv-footer">
  <div class="container">
    <div class="jv-footer-main">
      <div class="jv-footer-brand">
        <img src="<?= BASE_URL ?>img/logo jv.png" alt="<?= sanitize($siteName) ?>" style="height:48px;margin-bottom:12px">
        <p>Tu tienda online de confianza. Ofrecemos productos de calidad y servicios profesionales con entrega a todo el país.</p>
        <div class="jv-footer-social">
          <?php if($fb): ?><a href="<?=$fb?>" target="_blank"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
          <?php if($ig): ?><a href="<?=$ig?>" target="_blank"><i class="fab fa-instagram"></i></a><?php endif; ?>
          <?php if($tk): ?><a href="<?=$tk?>" target="_blank"><i class="fab fa-tiktok"></i></a><?php endif; ?>
          <a href="https://wa.me/<?=$whatsapp?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>
      <div>
        <h4>Tienda</h4>
        <ul>
          <li><a href="<?= BASE_URL ?>tienda.php">Todos los Productos</a></li>
          <li><a href="<?= BASE_URL ?>tienda.php?destacado=1">Destacados</a></li>
          <li><a href="<?= BASE_URL ?>tienda.php?nuevo=1">Novedades</a></li>
          <li><a href="<?= BASE_URL ?>tienda.php?oferta=1">Ofertas</a></li>
          <li><a href="<?= BASE_URL ?>servicios.php">Servicios</a></li>
        </ul>
      </div>
      <div>
        <h4>Mi Cuenta</h4>
        <ul>
          <li><a href="<?= BASE_URL ?>login.php">Iniciar Sesión</a></li>
          <li><a href="<?= BASE_URL ?>registro.php">Crear Cuenta</a></li>
          <li><a href="<?= BASE_URL ?>cliente/pedidos.php">Mis Pedidos</a></li>
          <li><a href="<?= BASE_URL ?>carrito.php">Mi Carrito</a></li>
        </ul>
      </div>
      <div>
        <h4>Contacto</h4>
        <ul>
          <li><a href="<?= BASE_URL ?>nosotros.php">Sobre Nosotros</a></li>
          <li><a href="<?= BASE_URL ?>contacto.php">Contáctanos</a></li>
          <li><a href="https://wa.me/<?=$whatsapp?>" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a></li>
          <li><a href="mailto:<?=$email?>"><?= sanitize($email) ?></a></li>
        </ul>
      </div>
    </div>
    <div class="jv-footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= sanitize($siteName) ?>. Todos los derechos reservados.</span>
      <span style="display:flex;align-items:center;gap:12px;">
        <a href="https://jiyanedesign.com" target="_blank" rel="noopener"
           style="display:inline-flex;align-items:center;gap:7px;color:rgba(200,212,232,0.55);font-size:12px;font-weight:500;text-decoration:none;letter-spacing:0.3px;transition:all 0.3s ease;border-bottom:1px solid transparent;padding-bottom:1px;"
           onmouseover="this.style.color='#0ea5e9';this.style.borderBottomColor='rgba(14,165,233,0.4)'"
           onmouseout="this.style.color='rgba(200,212,232,0.55)';this.style.borderBottomColor='transparent'">
          <i class="fas fa-code" style="font-size:10px;opacity:0.7;"></i>
          Desarrollado por <strong style="font-weight:700;letter-spacing:0.5px;">JiyandeDesign</strong>
        </a>
        <a href="<?= BASE_URL ?>admin/" style="color:inherit;opacity:0.35;text-decoration:none;font-size:11px;transition:opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='0.35'">Acceso Admin</a>
      </span>
    </div>
  </div>
</footer>

<!-- WhatsApp Float -->
<a href="https://wa.me/<?= $whatsapp ?>?text=Hola,%20me%20interesa%20un%20producto%20de%20<?= urlencode($siteName) ?>"
   target="_blank"
   style="position:fixed;bottom:24px;right:24px;z-index:999;width:54px;height:54px;background:#25D366;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;color:#fff;box-shadow:0 4px 20px rgba(37,211,102,.4);transition:transform .3s"
   onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
  <i class="fab fa-whatsapp"></i>
</a>

<script src="<?= BASE_URL ?>js/jvstore.js?v=2.0"></script>
<script src="<?= BASE_URL ?>js/cart.js?v=3.0"></script>
<?php if(isset($extraScripts)) foreach($extraScripts as $s): ?>
<script src="<?= BASE_URL ?>js/<?= $s ?>"></script>
<?php endforeach; ?>
</body>
</html>