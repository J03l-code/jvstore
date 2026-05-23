<?php
$pageTitle = 'Nosotros';
$pageDescription = 'Conoce la historia y misión de Impordispac.';
require_once 'includes/header.php';
?>

<section class="about-hero"
    style="background-image: linear-gradient(rgba(10, 25, 47, 0.7), rgba(10, 25, 47, 0.8)), url('<?= BASE_URL ?>img/backgrounds/nosotros.jpg');">
    <div class="container">
        <h1>Sobre Impordispac</h1>
        <p>Más de una década conectando mecánicos y talleres con repuestos de calidad mundial a precios competitivos.
        </p>
    </div>
</section>

<section class="section" style="background:var(--blanco);">
    <div class="container">
        <div class="feature-grid">
            <div class="feature-item">
                <div class="icon-box"><i class="fas fa-bullseye"></i></div>
                <div>
                    <h3>Nuestra Misión</h3>
                    <p>Facilitar el acceso a repuestos automotrices de alta calidad mediante la importación directa,
                        eliminando intermediarios y ofreciendo precios justos a talleres, mecánicos y usuarios finales
                        en todo el país.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="icon-box"><i class="fas fa-eye"></i></div>
                <div>
                    <h3>Nuestra Visión</h3>
                    <p>Ser la plataforma líder de distribución de repuestos automotrices en Ecuador, reconocida por su
                        calidad, servicio al cliente excepcional y compromiso con la innovación tecnológica.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="icon-box"><i class="fas fa-ship"></i></div>
                <div>
                    <h3>Importación Directa</h3>
                    <p>Trabajamos directamente con fabricantes en Asia, Europa y América del Norte. Sin intermediarios
                        significa mejor precio y mejor calidad para tu vehículo.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="icon-box"><i class="fas fa-certificate"></i></div>
                <div>
                    <h3>Control de Calidad</h3>
                    <p>Cada lote de repuestos pasa por un riguroso proceso de inspección antes de llegar a nuestro
                        inventario. Solo distribuimos piezas que cumplan con estándares OEM.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Nuestros Números</h2>
            <p>Resultados que respaldan nuestra trayectoria</p>
            <div class="line"></div>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue"><i class="fas fa-box-open"></i></div>
                <div class="stat-info">
                    <h4>Productos</h4>
                    <div class="stat-number">500+</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h4>Clientes</h4>
                    <div class="stat-number">1,000+</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange"><i class="fas fa-globe-americas"></i></div>
                <div class="stat-info">
                    <h4>Marcas</h4>
                    <div class="stat-number">50+</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-red"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <h4>Satisfacción</h4>
                    <div class="stat-number">98%</div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>