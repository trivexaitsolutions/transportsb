<?php
header('Content-Type: text/plain');

echo "LIVE TEST: 11-09-2026 NEW CODE\n";
echo "FILE: " . __FILE__ . "\n";
echo "DIR: " . __DIR__ . "\n";
echo "OPCACHE: " . ini_get('opcache.enable') . "\n";
echo "VALIDATE TIMESTAMPS: " . ini_get('opcache.validate_timestamps') . "\n";
echo "REVALIDATE FREQ: " . ini_get('opcache.revalidate_freq') . "\n";