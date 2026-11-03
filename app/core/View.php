<?php
// Renders a view file inside a layout. Views only print data they are given.

class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'public'): void
    {
        $content = self::capture(APP_PATH . "/views/$view.php", $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::capture(APP_PATH . "/views/layouts/$layout.php", $data + ['content' => $content]);
    }

    public static function partial(string $name, array $data = []): void
    {
        echo self::capture(APP_PATH . "/views/partials/$name.php", $data);
    }

    private static function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return ob_get_clean();
    }
}
