<?php

declare(strict_types=1);

require_once __DIR__ . '/HotelModule.php';

use App\Core\ModuleLoader;

ModuleLoader::register('hotel', [
    'name'                => 'Hotel Module',
    'ensure_schema'       => 'hotel_ensure_schema',
    'handle_admin'        => 'hotel_handle_admin',
    'handle_frontend'     => 'hotel_handle_frontend',
    'install'             => 'hotel_ensure_schema',
    'admin_stats'         => 'hotel_admin_stats',
    'dashboard_widgets'   => 'hotel_dashboard_widgets',
    'home_data'           => 'hotel_home_data',
    'filter_page_content' => 'hotel_filter_page_content',
    'head_scripts'        => 'hotel_head_scripts',
    'render_page_template' => 'hotel_render_page_template',
    'page_templates'       => 'hotel_page_templates',
    'admin_nav_group'     => 'Hotel',
    'admin_nav'           => [
        '/admin/hotel/rooms'      => 'Номери',
        '/admin/hotel/amenities'  => 'Зручності',
        '/admin/hotel/promotions' => 'Акції',
        '/admin/hotel/galleries'  => 'Галереї',
        '/admin/hotel/leads'      => 'Заявки',
        '/admin/hotel/tax'        => 'Туристичний збір',
        '/admin/hotel/booking'    => 'Exely / Бронювання',
    ],
]);
