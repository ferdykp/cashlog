<?php

$publicPath = getcwd();
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($uri !== '/' && $uri !== '/build/sw.js' && file_exists($publicPath.$uri)) {
    return false;
}

if ($uri === '/build/sw.js') {
    header('Service-Worker-Allowed: /');
    header('Content-Type: text/javascript');
    readfile(__DIR__.'/public/build/sw.js');
    exit;
}

require_once $publicPath.'/index.php';
