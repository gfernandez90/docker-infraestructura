<div class="p-4">
    <h1 class="text-2xl font-bold text-slate-800">Bienvenido al Dashboard</h1>
    <p class="text-slate-600 mt-2">Sesión iniciada como: <strong><?= htmlspecialchars($_SESSION['user']['username'] ?? '') ?></strong></p>
</div>
