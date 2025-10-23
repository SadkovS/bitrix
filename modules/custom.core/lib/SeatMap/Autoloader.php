<?php

namespace SeatMap;

spl_autoload_register(function ($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $className = preg_replace('/^SeatMap\//', '', $className);
    require_once __DIR__ . '/' . $className . '.php';
});
