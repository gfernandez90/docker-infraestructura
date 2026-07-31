<?php
require_once __DIR__ . '/../services/RedmineService.php';

$redmine = new RedmineService();

echo "<h2>1. Categorías asignadas al proyecto 'incidentes-diarios':</h2>";
try {
    $categories = $redmine->getRequest('/projects/incidentes-diarios/categories.json');
    echo "<pre>";
    print_r($categories);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<hr><h2>2. Buscar categorías desde las peticiones recientes de 'incidentes-diarios':</h2>";
try {
    // Consultamos las últimas peticiones incluyendo la categoría
    $issues = $redmine->getRequest('/issues.json?project_id=incidentes-diarios&include=category&limit=10');
    
    $categoriasEncontradas = [];
    if (!empty($issues['issues'])) {
        foreach ($issues['issues'] as $issue) {
            if (isset($issue['category'])) {
                $categoriasEncontradas[$issue['category']['id']] = $issue['category']['name'];
            }
        }
    }

    echo "Categorías extraídas de los últimos tickets:<br><pre>";
    print_r($categoriasEncontradas);
    echo "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
