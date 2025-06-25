<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Главная
Breadcrumbs::for('admin-dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('', route('admin.dashboard'));
});

// Можно добавить для других страниц:
// Breadcrumbs::for('admin.tenants', function (BreadcrumbTrail $trail) {
//     $trail->parent('admin-dashboard');
//     $trail->push('Арендаторы', route('admin.tenants.index'));
// });
