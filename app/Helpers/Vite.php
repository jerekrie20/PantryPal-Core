<?php
namespace Helpers;

class Vite {
    public static function tags(): string {
        $devServer = getenv('VITE_DEV_SERVER') ?: 'https://localhost:5173';
        $entry     = 'src/js/main.js';
        $manifest  = APP_ROOT . '/public/dist/manifest.json';
        $env       = getenv('APP_ENV') ?: 'development';
        $isDev     = ($env !== 'production') && is_dir(APP_ROOT . '/node_modules');

        if ($isDev) {
            $ts = time();
            $client   = $devServer . '/@vite/client?' . $ts;
            $entryUrl = $devServer . '/' . $entry . '?' . $ts;
            $clientEsc = htmlspecialchars($client, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $entryEsc  = htmlspecialchars($entryUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return <<<HTML
<script type="module" src="{$clientEsc}"></script>
<script type="module" src="{$entryEsc}"></script>
HTML;
        }

        if (is_file($manifest)) {
            $data = json_decode(file_get_contents($manifest), true);
            if (!empty($data[$entry]['file'])) {
                $js = '/dist/' . $data[$entry]['file'];
                $css = '';
                if (!empty($data[$entry]['css'])) {
                    foreach ($data[$entry]['css'] as $cssFile) {
                        $href = htmlspecialchars('/dist/' . $cssFile, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $css .= '<link rel="stylesheet" href="' . $href . '">' . PHP_EOL;
                    }
                }
                $jsEsc = htmlspecialchars($js, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                return $css . '<script type="module" src="' . $jsEsc . '"></script>';
            }
        }
        return '';
    }
}
