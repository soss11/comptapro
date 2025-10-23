<?php
/**
 * Classe Router - Système de routage
 */

class Router {
    private $routes = [];

    /**
     * Ajouter une route
     */
    public function add($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Dispatcher - Traiter la requête
     */
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_GET['url'] ?? '';
        $path = '/' . trim($path, '/');

        // Si chemin vide, rediriger vers /
        if ($path === '/') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertPathToRegex($route['path']);

            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Retirer le match complet

                return $this->callHandler($route['handler'], $matches);
            }
        }

        // 404 - Route non trouvée
        $this->notFound();
    }

    /**
     * Convertir un chemin en regex
     */
    private function convertPathToRegex($path) {
        $path = preg_replace('/\//', '\\/', $path);
        $path = preg_replace('/:([a-zA-Z0-9_]+)/', '([a-zA-Z0-9_-]+)', $path);
        return '/^' . $path . '$/';
    }

    /**
     * Appeler le handler
     */
    private function callHandler($handler, $params = []) {
        list($controller, $method) = explode('@', $handler);

        $controllerFile = __DIR__ . '/controllers/' . $controller . '.php';

        if (!file_exists($controllerFile)) {
            die("Controller not found: $controller");
        }

        require_once $controllerFile;

        if (!class_exists($controller)) {
            die("Controller class not found: $controller");
        }

        $controllerInstance = new $controller();

        if (!method_exists($controllerInstance, $method)) {
            die("Method not found: $controller::$method");
        }

        return call_user_func_array([$controllerInstance, $method], $params);
    }

    /**
     * Page 404
     */
    private function notFound() {
        http_response_code(404);
        require __DIR__ . '/views/errors/404.php';
        exit;
    }
}
