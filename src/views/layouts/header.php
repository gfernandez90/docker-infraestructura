<header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6">
    <div class="text-slate-600 font-medium">
        Sistema de Gestión de Infraestructura
    </div>
    <div class="flex items-center gap-4">
        <span class="text-sm font-semibold text-slate-700">
            👤 <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Usuario') ?>
            <span class="text-xs font-normal text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">Admin</span>
        </span>
        <a href="/logout.php" class="text-sm text-red-600 hover:text-red-800 font-medium hover:underline">
            Cerrar sesión
        </a>
    </div>
</header>
