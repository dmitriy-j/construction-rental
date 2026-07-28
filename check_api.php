<?php
// Проверка API каталога
$json = file_get_contents('http://fap24.ru/api/equipment/110');
$d = json_decode($json);
echo "Title: " . ($d->title ?? '?') . "\n";
echo "final_price: " . ($d->final_price ?? '?') . "\n";
echo "base_price: " . ($d->base_price ?? '?') . "\n";
echo "DONE\n";
