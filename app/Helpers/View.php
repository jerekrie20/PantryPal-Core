<?php

namespace Helpers;

class View {
    public static function render(string $view, array $data = []): string {
        // Validate view name to avoid directory traversal
        if (!preg_match('/^[A-Za-z0-9_\/\.-]+$/', $view)) {
            throw new \InvalidArgumentException('Invalid view name');
        }
        $path = VIEW_PATH . '/' . $view . '.php';
        // Resolve and ensure the file is under VIEW_PATH
        $realView = realpath($path) ?: $path;
        $realBase = realpath(VIEW_PATH) ?: VIEW_PATH;
        if (strpos($realView, $realBase) !== 0 || !is_file($realView)) {
            throw new \RuntimeException('View not found');
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $realView;
        return ob_get_clean();
    }
}