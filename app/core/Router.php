<?php
// Matches "METHOD /path" to a controller method. {id} in a path becomes a parameter.

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void  { $this->add('GET', $path, $handler); }
    public function post(string $path, array $handler): void { $this->add('POST', $path, $handler); }

    private function add(string $method, string $path, array $handler): void
    {
        $pattern = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($path, '/') ?: '/') . '$#';
        $this->routes[] = [$method, $pattern, $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        $allowed = false;

        foreach ($this->routes as [$m, $pattern, $handler]) {
            if (!preg_match($pattern, $path, $match)) {
                continue;
            }
            if ($m !== $method) {
                $allowed = true;
                continue;
            }
            $params = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $handler;
            (new $class())->$action(...array_values($params));
            return;
        }

        http_response_code($allowed ? 405 : 404);
        View::render('errors/404', ['title' => 'Page not found'], 'public');
    }
}
