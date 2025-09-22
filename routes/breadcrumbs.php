<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Главная
Breadcrumbs::for('admin-dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('', route('admin.dashboard'));
});

// Для арендатора
Breadcrumbs::for('lessee.rental-requests.index', function ($trail) {
    $trail->push('Главная', route('lessee.dashboard'));
    $trail->push('Мои заявки', route('lessee.rental-requests.index'));
});

Breadcrumbs::for('lessee.rental-requests.create', function ($trail) {
    $trail->push('Главная', route('lessee.dashboard'));
    $trail->push('Мои заявки', route('lessee.rental-requests.index'));
    $trail->push('Новая заявка');
});

// Для арендодателя
Breadcrumbs::for('lessor.rental-requests.index', function ($trail) {
    $trail->push('Главная', route('lessor.dashboard'));
    $trail->push('Заявки на аренду');
});

// Можно добавить для других страниц:
// Breadcrumbs::for('admin.tenants', function (BreadcrumbTrail $trail) {
//     $trail->parent('admin-dashboard');
//     $trail->push('Арендаторы', route('admin.tenants.index'));
// });
