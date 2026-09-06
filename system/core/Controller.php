<?php
namespace System\Core;

class Controller
{
    protected function view($path, $data = [])
    {
        $layout = $data['layout'] ?? 'auth';
        unset($data['layout']);

        extract($data);
        ob_start();
        require BASE_PATH . "/views/$path.php";
        $content = ob_get_clean();

        require BASE_PATH . "/views/layout/{$layout}Layout.php";
    }

    protected function sanitize_input($value, $case = null)
    {
        $value = trim($value ?? '');
        $value = strip_tags($value);
        $value = preg_replace('/\s+/', ' ', $value);
        if ($case === 'upper')
            return strtoupper($value);
        if ($case === 'lower')
            return strtolower($value);
        if ($case === 'ucwords')
            return ucwords(strtolower($value));
        return $value;
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    protected function redirectBack(string $fallback = '/'): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $target = $fallback;

        if ($referer !== '') {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $currentHost = $_SERVER['HTTP_HOST'] ?? '';
            if ($refererHost === null || strcasecmp((string) $refererHost, $currentHost) === 0) {
                $target = $referer;
            }
        }

        $this->redirect($target);
    }
}
