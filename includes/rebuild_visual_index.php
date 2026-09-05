<?php

function rebuild_visual_index(): bool
{
    $root = dirname(__DIR__);
    $apiDir = $root . DIRECTORY_SEPARATOR . 'api';
    $script = $apiDir . DIRECTORY_SEPARATOR . 'index_catalog.py';

    $pythonCandidates = [
        $apiDir . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python',
        $apiDir . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe',
        'python3',
    ];

    $python = null;
    foreach ($pythonCandidates as $candidate) {
        if ($candidate === 'python3' || is_file($candidate)) {
            $python = $candidate;
            break;
        }
    }

    if ($python === null) {
        error_log('Visual index rebuild skipped: Python not found.');
        return false;
    }

    if (is_file($script)) {
        $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script);
    } else {
        error_log('Visual index rebuild skipped: training/index scripts missing.');
        return false;
    }

    exec($cmd . ' 2>&1', $output, $status);
    if ($status !== 0) {
        error_log('Visual index rebuild failed: ' . implode(PHP_EOL, $output));
        return false;
    }

    return true;
}
