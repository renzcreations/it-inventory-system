<?php
namespace System\Core;

use Models\CatalogModel;
use System\Services\AuthorizationService;
use Throwable;

class Controller
{
    protected function view($path, $data = [])
    {
        $layout = $data['layout'] ?? 'auth';
        unset($data['layout']);

        $settings = [];
        $navigation = [];
        $permissions = [];
        if (!empty($_SESSION['user_id'])) {
            try {
                $catalogs = new CatalogModel();
                $settings = $catalogs->settings();
                $navigation = $catalogs->navigation();
                $permissions = (new AuthorizationService())->all();
            } catch (Throwable $exception) {
                error_log('Shared CMS data unavailable: ' . $exception->getMessage());
            }
        }
        $data['appSettings'] = $data['appSettings'] ?? $settings;
        $data['navigation'] = $data['navigation'] ?? $navigation;
        $data['permissions'] = $data['permissions'] ?? $permissions;

        extract($data);
        ob_start();
        require BASE_PATH . "/views/$path.php";
        $content = ob_get_clean();

        $token = htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8');
        $content = preg_replace_callback(
            '/<form\b([^>]*)>/i',
            static function (array $matches) use ($token): string {
                if (!preg_match('/\bmethod\s*=\s*["\']?post\b/i', $matches[1])) {
                    return $matches[0];
                }
                return $matches[0] . '<input type="hidden" name="_token" value="' . $token . '">';
            },
            $content
        );

        require BASE_PATH . "/views/layout/{$layout}Layout.php";
    }

    protected function authorize(string $permission): void
    {
        if (!(new AuthorizationService())->can($permission)) {
            http_response_code(403);
            exit('You do not have permission to perform this action.');
        }
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
