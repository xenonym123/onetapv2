<?php
// Base controller: rendering, redirects, JSON, login checks, and form helpers.

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'public'): void
    {
        View::render($view, $data, $layout);
    }

    /** Dashboard pages: adds the sidebar for the logged-in role. */
    protected function dashboard(string $view, array $data = []): void
    {
        View::render($view, $data + ['me' => Auth::user()], 'dashboard');
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /** Stops the request unless the user is logged in with this role. */
    protected function requireRole(string $role): array
    {
        $user = Auth::user();
        if (!$user) {
            flash('error', 'Please sign in first.');
            $this->redirect('/login');
        }
        if ($user['role'] !== $role) {
            $this->redirect(Auth::home());
        }
        return $user;
    }

    /** Every POST must carry the CSRF token from csrf_field() or the X-CSRF header. */
    protected function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? null);
        if (!Auth::checkCsrf($token)) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'message' => 'Your session expired. Reload the page.'], 419);
            }
            flash('error', 'Your session expired. Please try again.');
            $this->back();
        }
    }

    protected function back(string $fallback = '/'): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) {
            header('Location: ' . $ref);
            exit;
        }
        $this->redirect($fallback);
    }

    protected function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
    }

    protected function input(string $key, string $default = ''): string
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    /** Body of a fetch() JSON request. */
    protected function jsonBody(): array
    {
        $data = json_decode(file_get_contents('php://input') ?: '[]', true);
        return is_array($data) ? $data : [];
    }

    protected function csv(string $filename, array $header, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // so Excel reads ₱ correctly
        fputcsv($out, $header);
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        exit;
    }
}
