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
}