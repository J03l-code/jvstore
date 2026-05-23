<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

echo "<h1>Depuración de Google Login</h1>";
echo "<p>Copia EXACTAMENTE esta URL y pégala en Google Search Console > Credenciales > URIs de redireccionamiento:</p>";
echo "<div style='background:#f0f0f0; padding:15px; border:1px solid #ccc; font-family:monospace; font-size:1.2em;'>";
echo GOOGLE_REDIRECT_URI;
echo "</div>";

echo "<br><hr><br>";
echo "<p>Tu BASE_URL actual es: <code>" . BASE_URL . "</code></p>";
echo "<p>Tu GOOGLE_CLIENT_ID es: <code>" . GOOGLE_CLIENT_ID . "</code></p>";
echo "<p><a href='login.php'>Volver al Login</a></p>";
