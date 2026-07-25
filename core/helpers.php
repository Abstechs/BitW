<?php

// function clean($data) {
//     return htmlspecialchars(trim($data));
// }


function clean(mixed $data): string {
    return htmlspecialchars(trim((string) $data), ENT_QUOTES, 'UTF-8');
}

function generateRef($prefix = "BITW") {
    return $prefix . rand(100000, 999999);
}

function now() {
    return date("Y-m-d H:i:s");
}

/**
 * Get a value from a config array.
 *
 * @param array $config
 * @param string $key
 * @return mixed
 */
function app(array $config, string $key): mixed {
    return $config[$key] ?? null;
}