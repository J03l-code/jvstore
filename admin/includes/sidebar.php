<aside class="admin-sidebar">
    <div class="sidebar-logo">
        <img src="<?= BASE_URL ?>img/logojvn.png?v=<?= time() ?>" alt="JVN store"
            class="sidebar-logo-img">
        <small>Panel de Administración</small>
    </div>
    <nav class="sidebar-menu">
        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        ?>
        <a href="<?= BASE_URL ?>admin/" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?= BASE_URL ?>admin/productos.php" class="<?= $currentPage === 'productos.php' ? 'active' : '' ?>">
            <i class="fas fa-box-open"></i> Productos
        </a>
        <a href="<?= BASE_URL ?>admin/pedidos.php" class="<?= $currentPage === 'pedidos.php' ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i> Pedidos
        </a>
        <a href="<?= BASE_URL ?>admin/clientes.php" class="<?= $currentPage === 'clientes.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Clientes
        </a>
        <a href="<?= BASE_URL ?>admin/opiniones.php" class="<?= $currentPage === 'opiniones.php' ? 'active' : '' ?>">
            <i class="fas fa-comment-dots"></i> Opiniones
        </a>

        <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>admin/usuarios.php" class="<?= $currentPage === 'usuarios.php' ? 'active' : '' ?>">
                <i class="fas fa-user-shield"></i> Usuarios
            </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>" target="_blank">
            <i class="fas fa-external-link-alt"></i> Ver Tienda
        </a>
        <a href="<?= BASE_URL ?>logout.php">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </nav>
</aside>