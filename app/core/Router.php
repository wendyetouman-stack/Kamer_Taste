<?php
class Router
{
    public function dispatch(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

      // Retire le sous-dossier éventuel (XAMPP uniquement, pas sur Vercel)
$surVercel = getenv('VERCEL') !== false || isset($_SERVER['VERCEL']);
if (!$surVercel) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
}

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        if (($segments[0] ?? '') === 'admin') {
            array_shift($segments);
            $name  = $segments[0] ?? 'dashboard';
            $class = $name === 'dashboard'
                ? 'DashboardController'
                : ucfirst($name) . 'AdminController';
        } else {
            $name  = $segments[0] ?? 'home';
            $class = ucfirst($name) . 'Controller';
        }
        array_shift($segments);

        $method = $segments[0] ?? 'index';
        array_shift($segments);

        // Sécurité : on n'accepte que des noms simples
        if (!preg_match('/^[a-z0-9]+$/i', $name) || !preg_match('/^[a-z][a-z0-9_]*$/i', $method)) {
            $this->notFound();
            return;
        }

        if (!class_exists($class)) {
            $this->notFound();
            return;
        }

        $controller = new $class();
        if (!is_callable([$controller, $method])) {
            $this->notFound();
            return;
        }

        call_user_func_array([$controller, $method], $segments);
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo '<h1>404 - Page introuvable</h1>';
    }
}