<?php
$pageTitle = 'Nosotros';
$pageDescription = 'Conoce la historia y misión de JVN store.';
require_once 'includes/header.php';
?>

<section class="about-hero"
    style="background-image: linear-gradient(rgba(10, 25, 47, 0.7), rgba(10, 25, 47, 0.8)), url('<?= BASE_URL ?>img/backgrounds/nosotros.jpg');">
    <div class="container">
        <h1>Sobre JVN store</h1>
        <p>Más de una década ofreciendo productos de consumo y servicios profesionales de calidad mundial a precios competitivos.
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
                    <p>Facilitar el acceso a productos y servicios profesionales de alta calidad mediante la importación y distribución directa,
                        eliminando intermediarios y ofreciendo precios justos a clientes y negocios
                        en todo el país.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="icon-box"><i class="fas fa-eye"></i></div>
                <div>
                    <h3>Nuestra Visión</h3>
                    <p>Ser la plataforma de comercio electrónico y servicios líder en Ecuador, reconocida por su
                        calidad excepcional, atención personalizada y constante innovación tecnológica.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="icon-box"><i class="fas fa-ship"></i></div>
                <div>
                    <h3>Distribución Directa</h3>
                    <p>Trabajamos directamente con fabricantes y proveedores internacionales. Sin intermediarios
                        significa mejores precios y la más alta calidad garantizada para ti.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="icon-box"><i class="fas fa-certificate"></i></div>
                <div>
                    <h3>Control de Calidad</h3>
                    <p>Cada lote de productos pasa por un riguroso proceso de inspección antes de llegar a nuestro
                        inventario. Solo distribuimos artículos de primera categoría.</p>
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