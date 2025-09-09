<?php

app('router')->setCompiledRoutes(
    array (
  'compiled' => 
  array (
    0 => false,
    1 => 
    array (
      '/admin/login' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.auth.login',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/password-reset/request' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.auth.password-reset.request',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/password-reset/reset' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.auth.password-reset.reset',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/register' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.auth.register',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/logout' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.auth.logout',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/profile' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.auth.profile',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/email-verification/prompt' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.auth.email-verification.prompt',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.pages.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api-keys' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.api-keys.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/api-keys/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.api-keys.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/brands' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.brands.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/brands/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.brands.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/bundling-availability' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.bundling-availability.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/bundling-photos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.bundling-photos.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/bundling-photos/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.bundling-photos.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/bundlings' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.bundlings.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/bundlings/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.bundlings.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/categories' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.categories.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/categories/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.categories.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/customer-phone-numbers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.customer-phone-numbers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/customer-phone-numbers/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.customer-phone-numbers.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/customer-photos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.customer-photos.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/customer-photos/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.customer-photos.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/customers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.customers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/customers/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.customers.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/product-availability' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.product-availability.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/product-photos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.product-photos.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/product-photos/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.product-photos.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/products' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.products.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/products/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.products.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/product-specifications' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.product-specifications.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/product-specifications/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.product-specifications.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/promos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.promos.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/promos/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.promos.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/rental-includes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.rental-includes.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/rental-includes/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.rental-includes.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sub-categories' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.sub-categories.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sub-categories/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.sub-categories.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/transactions' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.transactions.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/transactions/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.transactions.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/users' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.users.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/users/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.users.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/shield/roles' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.shield.roles.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/shield/roles/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.shield.roles.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/activitylogs' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.activitylogs.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sanctum/csrf-cookie' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sanctum.csrf-cookie',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/livewire/update' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'livewire.update',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/livewire/livewire.js' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::OZE2o9PXDjtUPbyk',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/livewire/livewire.min.js.map' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::dZadz7o2rN4zo8pQ',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/livewire/upload-file' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'livewire.upload-file',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/user' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::DE0VjEnmyA7v6o7F',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/regions/provinces' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.provinces',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/server/stop' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::MAKgTzdFQCoMyY48',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/sendText' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::8OiqWWOmBjXhXEqB',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/categories' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::9QI1axFBnCL2gjpQ',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::fitgGkw88jxyCKvg',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/brands' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::WWh6OeYRSzxAF37d',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::lGLOUUqcpDBUZ1Wh',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/brands-premiere' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::aBKLvlg2mNLrgGnX',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/products' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::nK9BI3G7JzVDaTJb',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::nEeFFCm7N5nwChEi',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/BrowseProduct' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::YRNeoHhPga5Oqf6u',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/bundlings' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::jf1kE6ubmbwdorwH',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::4ojjSdJxg4HymDIm',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/sub-categories' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::9OcRnYkhuvEIXhLg',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::dx2auV63bsRtcBAq',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/search-suggestions' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::7nZtRRp8MWA4Vp2V',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/transactions' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::J5dENcvS4s6almO8',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/transactions/check-availability' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::6GLuNNuWHHKZYCo2',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/search' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::xN59tvAMXK3FX0mU',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/search/autocomplete' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Gna5OoIfrH2hJk6W',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/search/popular' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::c4aEVq1QCTOmDtvV',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/search/stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::hDhj40aLriTxk0jz',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/availability/check' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::vO7IczRG05c6OCLG',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/availability/check-multiple' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::nl74WB1pktnRvo8F',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/availability/unavailable-dates' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::JC51MN17spUWRsZf',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/availability/check-range' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::66JBzFfVcykFONOv',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/availability/check-cart' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::f1BKqpvbW091ZWHD',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/availability/stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::BbWQC26GCMLPXjY4',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/google-sheet-sync' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::hz8dVZPVFdESAFLX',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/google-sheet-export' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::77ZuxNvS6f56W3s4',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/transactions-check' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'transactions-check.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'transactions-check.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/transaction' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::875z2NTzzd1nTCVK',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/check-transaction' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::wziA9UBKtaSsqzA9',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/import/queue-status' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::bN578xExtcgSoX0Z',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/up' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::z5B765MVgYmzxTgi',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::TIsWwcUU8qB1SJza',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::3LX0yG9GWNqNvXGc',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/test-log' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::7VD1JptuWwO9gZH2',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/forgot-password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'forgot.password',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'reset.password',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/auth/google/callback' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'google.callback',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sync' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sync',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/register' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registration.form',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'registration.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/registration/success' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registration.success',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/search-products' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'product.search',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/check-email' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.check-email',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin-login' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::VkuAH726OmZbRgbx',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/customers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'customers.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/customers/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/customers/import/form' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.import.form',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/customers/import/template' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.import.template',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/customers/bulk-action/start' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.bulk-action.start',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/customers/bulk-action/progress' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.bulk-action.progress',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/whatsapp' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'whatsapp.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/whatsapp/login' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'whatsapp.login.form',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'whatsapp.login',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/whatsapp/auth-logout' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'whatsapp.auth.logout',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/whatsapp/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'whatsapp.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/whatsapp/status' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'whatsapp.status',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/whatsapp/qr' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'whatsapp.qr',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/whatsapp/test' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'whatsapp.test',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/whatsapp/restart' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'whatsapp.restart',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/whatsapp/logout-session' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'whatsapp.logout',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/whatsapp/logs' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'whatsapp.logs',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/failed-import/download' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'failed-import.download',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/failed-import/clear' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'failed-import.clear',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/import/download-failed' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'import.download-failed',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/import/view-errors' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'import.view-errors',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/import/statistics' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'import.statistics',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/import/failed-rows' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'import.failed-rows',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/import/clear-failed-data' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'import.clear-failed-data',
          ),
          1 => NULL,
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/import/reprocess-failed' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'import.reprocess-failed',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
    ),
    2 => 
    array (
      0 => '{^(?|/filament/(?|exports/([^/]++)/download(*:45)|imports/([^/]++)/failed\\-rows/download(*:90))|/a(?|dmin/(?|email\\-verification/verify/([^/]++)/([^/]++)(*:155)|a(?|pi\\-keys/([^/]++)(?|(*:187)|/edit(*:200))|ctivitylogs/([^/]++)(*:229))|b(?|rands/([^/]++)/edit(*:261)|undling(?|\\-photos/([^/]++)/edit(*:301)|s/([^/]++)/edit(*:324)))|c(?|ategories/([^/]++)/edit(*:361)|ustomer(?|\\-pho(?|ne\\-numbers/([^/]++)/edit(*:412)|tos/([^/]++)/edit(*:437))|s/([^/]++)(?|(*:459)|/edit(*:472))))|pro(?|duct(?|\\-(?|photos/([^/]++)/edit(*:521)|specifications/([^/]++)/edit(*:557))|s/([^/]++)/edit(*:581))|mos/([^/]++)/edit(*:607))|rental\\-includes/([^/]++)/edit(*:646)|s(?|ub\\-categories/([^/]++)/edit(*:686)|hield/roles/([^/]++)(?|(*:717)|/edit(*:730)))|transactions/([^/]++)(?|(*:764)|/edit(*:777))|users/([^/]++)/edit(*:805))|pi/(?|regions/(?|regencies/([^/]++)(*:849)|districts/([^/]++)(*:875)|villages/([^/]++)(*:900))|s(?|essions/([^/]++)/(?|restart(*:940)|logout(*:954)|start(*:967))|ub\\-categories/([^/]++)(?|(*:1002)|(*:1011)))|categor(?|y/([^/]++)(*:1042)|ies/([^/]++)(?|(*:1066)))|b(?|rand(?|/([^/]++)(*:1097)|s/([^/]++)(?|(*:1119)))|undling(?|/([^/]++)(*:1149)|s/([^/]++)(?|(*:1171))))|product(?|/([^/]++)(*:1202)|s/([^/]++)(?|(*:1224)))|transactions\\-check/([^/]++)(?|(*:1266))|import/(?|status/([^/]++)(*:1301)|results/([^/]++)(*:1326))))|/livewire/preview\\-file/([^/]++)(*:1370)|/pdf/([^/]++)(*:1392)|/email/verify/([^/]++)/([^/]++)(*:1432)|/customers/(?|([^/]++)(?|(*:1466)|/edit(*:1480)|(*:1489))|import(*:1505)|export(*:1520)|bulk\\-action(*:1541))|/storage/(.*)(*:1564))/?$}sDu',
    ),
    3 => 
    array (
      45 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.exports.download',
          ),
          1 => 
          array (
            0 => 'export',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      90 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.imports.failed-rows.download',
          ),
          1 => 
          array (
            0 => 'import',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      155 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.auth.email-verification.verify',
          ),
          1 => 
          array (
            0 => 'id',
            1 => 'hash',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      187 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.api-keys.view',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      200 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.api-keys.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      229 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.activitylogs.view',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      261 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.brands.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      301 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.bundling-photos.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      324 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.bundlings.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      361 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.categories.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      412 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.customer-phone-numbers.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      437 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.customer-photos.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      459 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.customers.view',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      472 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.customers.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      521 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.product-photos.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      557 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.product-specifications.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      581 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.products.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      607 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.promos.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      646 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.rental-includes.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      686 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.sub-categories.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      717 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.shield.roles.view',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      730 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.shield.roles.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      764 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.transactions.view',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      777 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.transactions.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      805 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'filament.admin.resources.users.edit',
          ),
          1 => 
          array (
            0 => 'record',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      849 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.regencies',
          ),
          1 => 
          array (
            0 => 'provinceId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      875 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.districts',
          ),
          1 => 
          array (
            0 => 'regencyId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      900 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.villages',
          ),
          1 => 
          array (
            0 => 'districtId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      940 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::HWpqLFIo0KwfsLCd',
          ),
          1 => 
          array (
            0 => 'session',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      954 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::PcvkMzvnykna0XcU',
          ),
          1 => 
          array (
            0 => 'session',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      967 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::hfxzJ8VKAwMvQcSf',
          ),
          1 => 
          array (
            0 => 'session',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1002 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::WWQyIEah89NkoOyt',
          ),
          1 => 
          array (
            0 => 'subCategory',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1011 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::0OlJLdiVajyRQxOA',
          ),
          1 => 
          array (
            0 => 'sub_category',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::mrHzeStq1lrYYK1P',
          ),
          1 => 
          array (
            0 => 'sub_category',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1042 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::LyuUByZmSmWRwr3h',
          ),
          1 => 
          array (
            0 => 'category',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1066 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::iaddcvOpZyQkCGGq',
          ),
          1 => 
          array (
            0 => 'category',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::9qm4hYe4RQKak5eR',
          ),
          1 => 
          array (
            0 => 'category',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1097 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::8rNGnI7pvFUT1sGM',
          ),
          1 => 
          array (
            0 => 'brand',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1119 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::XzSrsBKpv05E06Rz',
          ),
          1 => 
          array (
            0 => 'brand',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::7cMf8VpKQRecWz56',
          ),
          1 => 
          array (
            0 => 'brand',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1149 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::VSq7HuJMdKCFTqNM',
          ),
          1 => 
          array (
            0 => 'bundling',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1171 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::4mIHme0CH9Nv5cya',
          ),
          1 => 
          array (
            0 => 'bundling',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::HYlzzgZbxTgW9VLC',
          ),
          1 => 
          array (
            0 => 'bundling',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1202 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::DTjISVWB95RakSX5',
          ),
          1 => 
          array (
            0 => 'product',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1224 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::LoM3IsKLuuwCIdWg',
          ),
          1 => 
          array (
            0 => 'product',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::plaJBXteGTvqSIZr',
          ),
          1 => 
          array (
            0 => 'product',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1266 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'transactions-check.show',
          ),
          1 => 
          array (
            0 => 'transactions_check',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'transactions-check.update',
          ),
          1 => 
          array (
            0 => 'transactions_check',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'transactions-check.destroy',
          ),
          1 => 
          array (
            0 => 'transactions_check',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1301 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::1co0KXJDSEZeVHGV',
          ),
          1 => 
          array (
            0 => 'importId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1326 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::M0af4hqSKdKhMf3K',
          ),
          1 => 
          array (
            0 => 'importId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1370 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'livewire.preview-file',
          ),
          1 => 
          array (
            0 => 'filename',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1392 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pdf',
          ),
          1 => 
          array (
            0 => 'order',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1432 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'verification.verify',
          ),
          1 => 
          array (
            0 => 'id',
            1 => 'hash',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1466 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.show',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1480 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.edit',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1489 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.update',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'customers.destroy',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1505 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.import',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1520 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.export',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1541 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.bulk-action',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1564 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'storage.local',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => NULL,
          1 => NULL,
          2 => NULL,
          3 => NULL,
          4 => false,
          5 => false,
          6 => 0,
        ),
      ),
    ),
    4 => NULL,
  ),
  'attributes' => 
  array (
    'filament.exports.download' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'filament/exports/{export}/download',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'filament.actions',
        ),
        'uses' => 'Filament\\Actions\\Exports\\Http\\Controllers\\DownloadExport@__invoke',
        'controller' => 'Filament\\Actions\\Exports\\Http\\Controllers\\DownloadExport',
        'as' => 'filament.exports.download',
        'namespace' => NULL,
        'prefix' => 'filament',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.imports.failed-rows.download' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'filament/imports/{import}/failed-rows/download',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'filament.actions',
        ),
        'uses' => 'Filament\\Actions\\Imports\\Http\\Controllers\\DownloadImportFailureCsv@__invoke',
        'controller' => 'Filament\\Actions\\Imports\\Http\\Controllers\\DownloadImportFailureCsv',
        'as' => 'filament.imports.failed-rows.download',
        'namespace' => NULL,
        'prefix' => 'filament',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.auth.login' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/login',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
        ),
        'uses' => 'Filament\\Pages\\Auth\\Login@__invoke',
        'controller' => 'Filament\\Pages\\Auth\\Login',
        'as' => 'filament.admin.auth.login',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.auth.password-reset.request' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/password-reset/request',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
        ),
        'uses' => 'Filament\\Pages\\Auth\\PasswordReset\\RequestPasswordReset@__invoke',
        'controller' => 'Filament\\Pages\\Auth\\PasswordReset\\RequestPasswordReset',
        'as' => 'filament.admin.auth.password-reset.request',
        'namespace' => NULL,
        'prefix' => 'admin/password-reset',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.auth.password-reset.reset' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/password-reset/reset',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'signed',
        ),
        'uses' => 'Filament\\Pages\\Auth\\PasswordReset\\ResetPassword@__invoke',
        'controller' => 'Filament\\Pages\\Auth\\PasswordReset\\ResetPassword',
        'as' => 'filament.admin.auth.password-reset.reset',
        'namespace' => NULL,
        'prefix' => 'admin/password-reset',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.auth.register' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/register',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
        ),
        'uses' => 'Filament\\Pages\\Auth\\Register@__invoke',
        'controller' => 'Filament\\Pages\\Auth\\Register',
        'as' => 'filament.admin.auth.register',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.auth.logout' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/logout',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
        ),
        'uses' => 'Filament\\Http\\Controllers\\Auth\\LogoutController@__invoke',
        'controller' => 'Filament\\Http\\Controllers\\Auth\\LogoutController',
        'as' => 'filament.admin.auth.logout',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.auth.profile' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/profile',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'uses' => 'Filament\\Pages\\Auth\\EditProfile@__invoke',
        'controller' => 'Filament\\Pages\\Auth\\EditProfile',
        'as' => 'filament.admin.auth.profile',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
        'excluded_middleware' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.auth.email-verification.prompt' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/email-verification/prompt',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
        ),
        'uses' => 'Filament\\Pages\\Auth\\EmailVerification\\EmailVerificationPrompt@__invoke',
        'controller' => 'Filament\\Pages\\Auth\\EmailVerification\\EmailVerificationPrompt',
        'as' => 'filament.admin.auth.email-verification.prompt',
        'namespace' => NULL,
        'prefix' => 'admin/email-verification',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.auth.email-verification.verify' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/email-verification/verify/{id}/{hash}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'signed',
          12 => 'throttle:6,1',
        ),
        'uses' => 'Filament\\Http\\Controllers\\Auth\\EmailVerificationController@__invoke',
        'controller' => 'Filament\\Http\\Controllers\\Auth\\EmailVerificationController',
        'as' => 'filament.admin.auth.email-verification.verify',
        'namespace' => NULL,
        'prefix' => 'admin/email-verification',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.pages.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'uses' => 'Filament\\Pages\\Dashboard@__invoke',
        'controller' => 'Filament\\Pages\\Dashboard',
        'as' => 'filament.admin.pages.dashboard',
        'namespace' => NULL,
        'prefix' => 'admin/',
        'where' => 
        array (
        ),
        'excluded_middleware' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.api-keys.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api-keys',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ApiKeyResource\\Pages\\ListApiKeys@__invoke',
        'controller' => 'App\\Filament\\Resources\\ApiKeyResource\\Pages\\ListApiKeys',
        'as' => 'filament.admin.resources.api-keys.index',
        'namespace' => NULL,
        'prefix' => 'admin/api-keys',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.api-keys.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api-keys/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ApiKeyResource\\Pages\\CreateApiKey@__invoke',
        'controller' => 'App\\Filament\\Resources\\ApiKeyResource\\Pages\\CreateApiKey',
        'as' => 'filament.admin.resources.api-keys.create',
        'namespace' => NULL,
        'prefix' => 'admin/api-keys',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.api-keys.view' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api-keys/{record}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ApiKeyResource\\Pages\\ViewApiKey@__invoke',
        'controller' => 'App\\Filament\\Resources\\ApiKeyResource\\Pages\\ViewApiKey',
        'as' => 'filament.admin.resources.api-keys.view',
        'namespace' => NULL,
        'prefix' => 'admin/api-keys',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.api-keys.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/api-keys/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ApiKeyResource\\Pages\\EditApiKey@__invoke',
        'controller' => 'App\\Filament\\Resources\\ApiKeyResource\\Pages\\EditApiKey',
        'as' => 'filament.admin.resources.api-keys.edit',
        'namespace' => NULL,
        'prefix' => 'admin/api-keys',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.brands.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/brands',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\BrandResource\\Pages\\ListBrands@__invoke',
        'controller' => 'App\\Filament\\Resources\\BrandResource\\Pages\\ListBrands',
        'as' => 'filament.admin.resources.brands.index',
        'namespace' => NULL,
        'prefix' => 'admin/brands',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.brands.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/brands/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\BrandResource\\Pages\\CreateBrand@__invoke',
        'controller' => 'App\\Filament\\Resources\\BrandResource\\Pages\\CreateBrand',
        'as' => 'filament.admin.resources.brands.create',
        'namespace' => NULL,
        'prefix' => 'admin/brands',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.brands.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/brands/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\BrandResource\\Pages\\EditBrand@__invoke',
        'controller' => 'App\\Filament\\Resources\\BrandResource\\Pages\\EditBrand',
        'as' => 'filament.admin.resources.brands.edit',
        'namespace' => NULL,
        'prefix' => 'admin/brands',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.bundling-availability.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/bundling-availability',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\BundlingAvailabilityResource\\Pages\\ListBundlingAvailabilities@__invoke',
        'controller' => 'App\\Filament\\Resources\\BundlingAvailabilityResource\\Pages\\ListBundlingAvailabilities',
        'as' => 'filament.admin.resources.bundling-availability.index',
        'namespace' => NULL,
        'prefix' => 'admin/bundling-availability',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.bundling-photos.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/bundling-photos',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\BundlingPhotoResource\\Pages\\ListBundlingPhotos@__invoke',
        'controller' => 'App\\Filament\\Resources\\BundlingPhotoResource\\Pages\\ListBundlingPhotos',
        'as' => 'filament.admin.resources.bundling-photos.index',
        'namespace' => NULL,
        'prefix' => 'admin/bundling-photos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.bundling-photos.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/bundling-photos/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\BundlingPhotoResource\\Pages\\CreateBundlingPhoto@__invoke',
        'controller' => 'App\\Filament\\Resources\\BundlingPhotoResource\\Pages\\CreateBundlingPhoto',
        'as' => 'filament.admin.resources.bundling-photos.create',
        'namespace' => NULL,
        'prefix' => 'admin/bundling-photos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.bundling-photos.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/bundling-photos/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\BundlingPhotoResource\\Pages\\EditBundlingPhoto@__invoke',
        'controller' => 'App\\Filament\\Resources\\BundlingPhotoResource\\Pages\\EditBundlingPhoto',
        'as' => 'filament.admin.resources.bundling-photos.edit',
        'namespace' => NULL,
        'prefix' => 'admin/bundling-photos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.bundlings.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/bundlings',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\BundlingResource\\Pages\\ListBundlings@__invoke',
        'controller' => 'App\\Filament\\Resources\\BundlingResource\\Pages\\ListBundlings',
        'as' => 'filament.admin.resources.bundlings.index',
        'namespace' => NULL,
        'prefix' => 'admin/bundlings',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.bundlings.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/bundlings/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\BundlingResource\\Pages\\CreateBundling@__invoke',
        'controller' => 'App\\Filament\\Resources\\BundlingResource\\Pages\\CreateBundling',
        'as' => 'filament.admin.resources.bundlings.create',
        'namespace' => NULL,
        'prefix' => 'admin/bundlings',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.bundlings.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/bundlings/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\BundlingResource\\Pages\\EditBundling@__invoke',
        'controller' => 'App\\Filament\\Resources\\BundlingResource\\Pages\\EditBundling',
        'as' => 'filament.admin.resources.bundlings.edit',
        'namespace' => NULL,
        'prefix' => 'admin/bundlings',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.categories.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/categories',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CategoryResource\\Pages\\ListCategories@__invoke',
        'controller' => 'App\\Filament\\Resources\\CategoryResource\\Pages\\ListCategories',
        'as' => 'filament.admin.resources.categories.index',
        'namespace' => NULL,
        'prefix' => 'admin/categories',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.categories.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/categories/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CategoryResource\\Pages\\CreateCategory@__invoke',
        'controller' => 'App\\Filament\\Resources\\CategoryResource\\Pages\\CreateCategory',
        'as' => 'filament.admin.resources.categories.create',
        'namespace' => NULL,
        'prefix' => 'admin/categories',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.categories.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/categories/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CategoryResource\\Pages\\EditCategory@__invoke',
        'controller' => 'App\\Filament\\Resources\\CategoryResource\\Pages\\EditCategory',
        'as' => 'filament.admin.resources.categories.edit',
        'namespace' => NULL,
        'prefix' => 'admin/categories',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.customer-phone-numbers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/customer-phone-numbers',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CustomerPhoneNumberResource\\Pages\\ListCustomerPhoneNumbers@__invoke',
        'controller' => 'App\\Filament\\Resources\\CustomerPhoneNumberResource\\Pages\\ListCustomerPhoneNumbers',
        'as' => 'filament.admin.resources.customer-phone-numbers.index',
        'namespace' => NULL,
        'prefix' => 'admin/customer-phone-numbers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.customer-phone-numbers.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/customer-phone-numbers/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CustomerPhoneNumberResource\\Pages\\CreateCustomerPhoneNumber@__invoke',
        'controller' => 'App\\Filament\\Resources\\CustomerPhoneNumberResource\\Pages\\CreateCustomerPhoneNumber',
        'as' => 'filament.admin.resources.customer-phone-numbers.create',
        'namespace' => NULL,
        'prefix' => 'admin/customer-phone-numbers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.customer-phone-numbers.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/customer-phone-numbers/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CustomerPhoneNumberResource\\Pages\\EditCustomerPhoneNumber@__invoke',
        'controller' => 'App\\Filament\\Resources\\CustomerPhoneNumberResource\\Pages\\EditCustomerPhoneNumber',
        'as' => 'filament.admin.resources.customer-phone-numbers.edit',
        'namespace' => NULL,
        'prefix' => 'admin/customer-phone-numbers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.customer-photos.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/customer-photos',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CustomerPhotoResource\\Pages\\ListCustomerPhotos@__invoke',
        'controller' => 'App\\Filament\\Resources\\CustomerPhotoResource\\Pages\\ListCustomerPhotos',
        'as' => 'filament.admin.resources.customer-photos.index',
        'namespace' => NULL,
        'prefix' => 'admin/customer-photos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.customer-photos.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/customer-photos/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CustomerPhotoResource\\Pages\\CreateCustomerPhoto@__invoke',
        'controller' => 'App\\Filament\\Resources\\CustomerPhotoResource\\Pages\\CreateCustomerPhoto',
        'as' => 'filament.admin.resources.customer-photos.create',
        'namespace' => NULL,
        'prefix' => 'admin/customer-photos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.customer-photos.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/customer-photos/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CustomerPhotoResource\\Pages\\EditCustomerPhoto@__invoke',
        'controller' => 'App\\Filament\\Resources\\CustomerPhotoResource\\Pages\\EditCustomerPhoto',
        'as' => 'filament.admin.resources.customer-photos.edit',
        'namespace' => NULL,
        'prefix' => 'admin/customer-photos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.customers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/customers',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CustomerResource\\Pages\\ListCustomers@__invoke',
        'controller' => 'App\\Filament\\Resources\\CustomerResource\\Pages\\ListCustomers',
        'as' => 'filament.admin.resources.customers.index',
        'namespace' => NULL,
        'prefix' => 'admin/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.customers.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/customers/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CustomerResource\\Pages\\CreateCustomer@__invoke',
        'controller' => 'App\\Filament\\Resources\\CustomerResource\\Pages\\CreateCustomer',
        'as' => 'filament.admin.resources.customers.create',
        'namespace' => NULL,
        'prefix' => 'admin/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.customers.view' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/customers/{record}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CustomerResource\\Pages\\ViewCustomer@__invoke',
        'controller' => 'App\\Filament\\Resources\\CustomerResource\\Pages\\ViewCustomer',
        'as' => 'filament.admin.resources.customers.view',
        'namespace' => NULL,
        'prefix' => 'admin/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.customers.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/customers/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\CustomerResource\\Pages\\EditCustomer@__invoke',
        'controller' => 'App\\Filament\\Resources\\CustomerResource\\Pages\\EditCustomer',
        'as' => 'filament.admin.resources.customers.edit',
        'namespace' => NULL,
        'prefix' => 'admin/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.product-availability.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/product-availability',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ProductAvailabilityResource\\Pages\\ListProductAvailabilities@__invoke',
        'controller' => 'App\\Filament\\Resources\\ProductAvailabilityResource\\Pages\\ListProductAvailabilities',
        'as' => 'filament.admin.resources.product-availability.index',
        'namespace' => NULL,
        'prefix' => 'admin/product-availability',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.product-photos.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/product-photos',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ProductPhotoResource\\Pages\\ListProductPhotos@__invoke',
        'controller' => 'App\\Filament\\Resources\\ProductPhotoResource\\Pages\\ListProductPhotos',
        'as' => 'filament.admin.resources.product-photos.index',
        'namespace' => NULL,
        'prefix' => 'admin/product-photos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.product-photos.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/product-photos/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ProductPhotoResource\\Pages\\CreateProductPhoto@__invoke',
        'controller' => 'App\\Filament\\Resources\\ProductPhotoResource\\Pages\\CreateProductPhoto',
        'as' => 'filament.admin.resources.product-photos.create',
        'namespace' => NULL,
        'prefix' => 'admin/product-photos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.product-photos.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/product-photos/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ProductPhotoResource\\Pages\\EditProductPhoto@__invoke',
        'controller' => 'App\\Filament\\Resources\\ProductPhotoResource\\Pages\\EditProductPhoto',
        'as' => 'filament.admin.resources.product-photos.edit',
        'namespace' => NULL,
        'prefix' => 'admin/product-photos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.products.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/products',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ProductResource\\Pages\\ListProducts@__invoke',
        'controller' => 'App\\Filament\\Resources\\ProductResource\\Pages\\ListProducts',
        'as' => 'filament.admin.resources.products.index',
        'namespace' => NULL,
        'prefix' => 'admin/products',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.products.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/products/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ProductResource\\Pages\\CreateProduct@__invoke',
        'controller' => 'App\\Filament\\Resources\\ProductResource\\Pages\\CreateProduct',
        'as' => 'filament.admin.resources.products.create',
        'namespace' => NULL,
        'prefix' => 'admin/products',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.products.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/products/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ProductResource\\Pages\\EditProduct@__invoke',
        'controller' => 'App\\Filament\\Resources\\ProductResource\\Pages\\EditProduct',
        'as' => 'filament.admin.resources.products.edit',
        'namespace' => NULL,
        'prefix' => 'admin/products',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.product-specifications.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/product-specifications',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ProductSpecificationResource\\Pages\\ListProductSpecifications@__invoke',
        'controller' => 'App\\Filament\\Resources\\ProductSpecificationResource\\Pages\\ListProductSpecifications',
        'as' => 'filament.admin.resources.product-specifications.index',
        'namespace' => NULL,
        'prefix' => 'admin/product-specifications',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.product-specifications.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/product-specifications/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ProductSpecificationResource\\Pages\\CreateProductSpecification@__invoke',
        'controller' => 'App\\Filament\\Resources\\ProductSpecificationResource\\Pages\\CreateProductSpecification',
        'as' => 'filament.admin.resources.product-specifications.create',
        'namespace' => NULL,
        'prefix' => 'admin/product-specifications',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.product-specifications.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/product-specifications/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\ProductSpecificationResource\\Pages\\EditProductSpecification@__invoke',
        'controller' => 'App\\Filament\\Resources\\ProductSpecificationResource\\Pages\\EditProductSpecification',
        'as' => 'filament.admin.resources.product-specifications.edit',
        'namespace' => NULL,
        'prefix' => 'admin/product-specifications',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.promos.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/promos',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\PromoResource\\Pages\\ListPromos@__invoke',
        'controller' => 'App\\Filament\\Resources\\PromoResource\\Pages\\ListPromos',
        'as' => 'filament.admin.resources.promos.index',
        'namespace' => NULL,
        'prefix' => 'admin/promos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.promos.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/promos/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\PromoResource\\Pages\\CreatePromo@__invoke',
        'controller' => 'App\\Filament\\Resources\\PromoResource\\Pages\\CreatePromo',
        'as' => 'filament.admin.resources.promos.create',
        'namespace' => NULL,
        'prefix' => 'admin/promos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.promos.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/promos/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\PromoResource\\Pages\\EditPromo@__invoke',
        'controller' => 'App\\Filament\\Resources\\PromoResource\\Pages\\EditPromo',
        'as' => 'filament.admin.resources.promos.edit',
        'namespace' => NULL,
        'prefix' => 'admin/promos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.rental-includes.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/rental-includes',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\RentalIncludeResource\\Pages\\ListRentalIncludes@__invoke',
        'controller' => 'App\\Filament\\Resources\\RentalIncludeResource\\Pages\\ListRentalIncludes',
        'as' => 'filament.admin.resources.rental-includes.index',
        'namespace' => NULL,
        'prefix' => 'admin/rental-includes',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.rental-includes.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/rental-includes/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\RentalIncludeResource\\Pages\\CreateRentalInclude@__invoke',
        'controller' => 'App\\Filament\\Resources\\RentalIncludeResource\\Pages\\CreateRentalInclude',
        'as' => 'filament.admin.resources.rental-includes.create',
        'namespace' => NULL,
        'prefix' => 'admin/rental-includes',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.rental-includes.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/rental-includes/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\RentalIncludeResource\\Pages\\EditRentalInclude@__invoke',
        'controller' => 'App\\Filament\\Resources\\RentalIncludeResource\\Pages\\EditRentalInclude',
        'as' => 'filament.admin.resources.rental-includes.edit',
        'namespace' => NULL,
        'prefix' => 'admin/rental-includes',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.sub-categories.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sub-categories',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\SubCategoryResource\\Pages\\ListSubCategories@__invoke',
        'controller' => 'App\\Filament\\Resources\\SubCategoryResource\\Pages\\ListSubCategories',
        'as' => 'filament.admin.resources.sub-categories.index',
        'namespace' => NULL,
        'prefix' => 'admin/sub-categories',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.sub-categories.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sub-categories/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\SubCategoryResource\\Pages\\CreateSubCategory@__invoke',
        'controller' => 'App\\Filament\\Resources\\SubCategoryResource\\Pages\\CreateSubCategory',
        'as' => 'filament.admin.resources.sub-categories.create',
        'namespace' => NULL,
        'prefix' => 'admin/sub-categories',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.sub-categories.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sub-categories/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\SubCategoryResource\\Pages\\EditSubCategory@__invoke',
        'controller' => 'App\\Filament\\Resources\\SubCategoryResource\\Pages\\EditSubCategory',
        'as' => 'filament.admin.resources.sub-categories.edit',
        'namespace' => NULL,
        'prefix' => 'admin/sub-categories',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.transactions.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/transactions',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\TransactionResource\\Pages\\ListTransactions@__invoke',
        'controller' => 'App\\Filament\\Resources\\TransactionResource\\Pages\\ListTransactions',
        'as' => 'filament.admin.resources.transactions.index',
        'namespace' => NULL,
        'prefix' => 'admin/transactions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.transactions.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/transactions/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\TransactionResource\\Pages\\CreateTransaction@__invoke',
        'controller' => 'App\\Filament\\Resources\\TransactionResource\\Pages\\CreateTransaction',
        'as' => 'filament.admin.resources.transactions.create',
        'namespace' => NULL,
        'prefix' => 'admin/transactions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.transactions.view' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/transactions/{record}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\TransactionResource\\Pages\\ViewTransaction@__invoke',
        'controller' => 'App\\Filament\\Resources\\TransactionResource\\Pages\\ViewTransaction',
        'as' => 'filament.admin.resources.transactions.view',
        'namespace' => NULL,
        'prefix' => 'admin/transactions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.transactions.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/transactions/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\TransactionResource\\Pages\\EditTransaction@__invoke',
        'controller' => 'App\\Filament\\Resources\\TransactionResource\\Pages\\EditTransaction',
        'as' => 'filament.admin.resources.transactions.edit',
        'namespace' => NULL,
        'prefix' => 'admin/transactions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.users.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/users',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\UserResource\\Pages\\ListUsers@__invoke',
        'controller' => 'App\\Filament\\Resources\\UserResource\\Pages\\ListUsers',
        'as' => 'filament.admin.resources.users.index',
        'namespace' => NULL,
        'prefix' => 'admin/users',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.users.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/users/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\UserResource\\Pages\\CreateUser@__invoke',
        'controller' => 'App\\Filament\\Resources\\UserResource\\Pages\\CreateUser',
        'as' => 'filament.admin.resources.users.create',
        'namespace' => NULL,
        'prefix' => 'admin/users',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.users.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/users/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'App\\Filament\\Resources\\UserResource\\Pages\\EditUser@__invoke',
        'controller' => 'App\\Filament\\Resources\\UserResource\\Pages\\EditUser',
        'as' => 'filament.admin.resources.users.edit',
        'namespace' => NULL,
        'prefix' => 'admin/users',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.shield.roles.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/shield/roles',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'BezhanSalleh\\FilamentShield\\Resources\\RoleResource\\Pages\\ListRoles@__invoke',
        'controller' => 'BezhanSalleh\\FilamentShield\\Resources\\RoleResource\\Pages\\ListRoles',
        'as' => 'filament.admin.resources.shield.roles.index',
        'namespace' => NULL,
        'prefix' => 'admin/shield/roles',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.shield.roles.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/shield/roles/create',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'BezhanSalleh\\FilamentShield\\Resources\\RoleResource\\Pages\\CreateRole@__invoke',
        'controller' => 'BezhanSalleh\\FilamentShield\\Resources\\RoleResource\\Pages\\CreateRole',
        'as' => 'filament.admin.resources.shield.roles.create',
        'namespace' => NULL,
        'prefix' => 'admin/shield/roles',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.shield.roles.view' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/shield/roles/{record}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'BezhanSalleh\\FilamentShield\\Resources\\RoleResource\\Pages\\ViewRole@__invoke',
        'controller' => 'BezhanSalleh\\FilamentShield\\Resources\\RoleResource\\Pages\\ViewRole',
        'as' => 'filament.admin.resources.shield.roles.view',
        'namespace' => NULL,
        'prefix' => 'admin/shield/roles',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.shield.roles.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/shield/roles/{record}/edit',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'BezhanSalleh\\FilamentShield\\Resources\\RoleResource\\Pages\\EditRole@__invoke',
        'controller' => 'BezhanSalleh\\FilamentShield\\Resources\\RoleResource\\Pages\\EditRole',
        'as' => 'filament.admin.resources.shield.roles.edit',
        'namespace' => NULL,
        'prefix' => 'admin/shield/roles',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.activitylogs.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/activitylogs',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'Rmsramos\\Activitylog\\Resources\\ActivitylogResource\\Pages\\ListActivitylog@__invoke',
        'controller' => 'Rmsramos\\Activitylog\\Resources\\ActivitylogResource\\Pages\\ListActivitylog',
        'as' => 'filament.admin.resources.activitylogs.index',
        'namespace' => NULL,
        'prefix' => 'admin/activitylogs',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'filament.admin.resources.activitylogs.view' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/activitylogs/{record}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'panel:admin',
          1 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
          2 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
          3 => 'Illuminate\\Session\\Middleware\\StartSession',
          4 => 'Filament\\Http\\Middleware\\AuthenticateSession',
          5 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
          6 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
          7 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
          8 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
          9 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
          10 => 'Filament\\Http\\Middleware\\Authenticate',
          11 => 'verified:filament.admin.auth.email-verification.prompt',
        ),
        'excluded_middleware' => 
        array (
        ),
        'uses' => 'Rmsramos\\Activitylog\\Resources\\ActivitylogResource\\Pages\\ViewActivitylog@__invoke',
        'controller' => 'Rmsramos\\Activitylog\\Resources\\ActivitylogResource\\Pages\\ViewActivitylog',
        'as' => 'filament.admin.resources.activitylogs.view',
        'namespace' => NULL,
        'prefix' => 'admin/activitylogs',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sanctum.csrf-cookie' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sanctum/csrf-cookie',
      'action' => 
      array (
        'uses' => 'Laravel\\Sanctum\\Http\\Controllers\\CsrfCookieController@show',
        'controller' => 'Laravel\\Sanctum\\Http\\Controllers\\CsrfCookieController@show',
        'namespace' => NULL,
        'prefix' => 'sanctum',
        'where' => 
        array (
        ),
        'middleware' => 
        array (
          0 => 'web',
        ),
        'as' => 'sanctum.csrf-cookie',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'livewire.update' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'livewire/update',
      'action' => 
      array (
        'uses' => 'Livewire\\Mechanisms\\HandleRequests\\HandleRequests@handleUpdate',
        'controller' => 'Livewire\\Mechanisms\\HandleRequests\\HandleRequests@handleUpdate',
        'middleware' => 
        array (
          0 => 'web',
        ),
        'as' => 'livewire.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::OZE2o9PXDjtUPbyk' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'livewire/livewire.js',
      'action' => 
      array (
        'uses' => 'Livewire\\Mechanisms\\FrontendAssets\\FrontendAssets@returnJavaScriptAsFile',
        'controller' => 'Livewire\\Mechanisms\\FrontendAssets\\FrontendAssets@returnJavaScriptAsFile',
        'as' => 'generated::OZE2o9PXDjtUPbyk',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::dZadz7o2rN4zo8pQ' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'livewire/livewire.min.js.map',
      'action' => 
      array (
        'uses' => 'Livewire\\Mechanisms\\FrontendAssets\\FrontendAssets@maps',
        'controller' => 'Livewire\\Mechanisms\\FrontendAssets\\FrontendAssets@maps',
        'as' => 'generated::dZadz7o2rN4zo8pQ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'livewire.upload-file' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'livewire/upload-file',
      'action' => 
      array (
        'uses' => 'Livewire\\Features\\SupportFileUploads\\FileUploadController@handle',
        'controller' => 'Livewire\\Features\\SupportFileUploads\\FileUploadController@handle',
        'as' => 'livewire.upload-file',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'livewire.preview-file' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'livewire/preview-file/{filename}',
      'action' => 
      array (
        'uses' => 'Livewire\\Features\\SupportFileUploads\\FilePreviewController@handle',
        'controller' => 'Livewire\\Features\\SupportFileUploads\\FilePreviewController@handle',
        'as' => 'livewire.preview-file',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::DE0VjEnmyA7v6o7F' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/user',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:77:"function (\\Illuminate\\Http\\Request $request) {
    return $request->user();
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000fd70000000000000000";}}',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::DE0VjEnmyA7v6o7F',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.provinces' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/regions/provinces',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\RegionController@getProvinces',
        'controller' => 'App\\Http\\Controllers\\Api\\RegionController@getProvinces',
        'namespace' => NULL,
        'prefix' => '/api/regions',
        'where' => 
        array (
        ),
        'as' => 'api.provinces',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.regencies' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/regions/regencies/{provinceId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\RegionController@getRegencies',
        'controller' => 'App\\Http\\Controllers\\Api\\RegionController@getRegencies',
        'namespace' => NULL,
        'prefix' => '/api/regions',
        'where' => 
        array (
        ),
        'as' => 'api.regencies',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.districts' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/regions/districts/{regencyId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\RegionController@getDistricts',
        'controller' => 'App\\Http\\Controllers\\Api\\RegionController@getDistricts',
        'namespace' => NULL,
        'prefix' => '/api/regions',
        'where' => 
        array (
        ),
        'as' => 'api.districts',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.villages' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/regions/villages/{districtId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\RegionController@getVillages',
        'controller' => 'App\\Http\\Controllers\\Api\\RegionController@getVillages',
        'namespace' => NULL,
        'prefix' => '/api/regions',
        'where' => 
        array (
        ),
        'as' => 'api.villages',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::MAKgTzdFQCoMyY48' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/server/stop',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@stopServer',
        'controller' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@stopServer',
        'namespace' => NULL,
        'prefix' => 'api/server',
        'where' => 
        array (
        ),
        'as' => 'generated::MAKgTzdFQCoMyY48',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::HWpqLFIo0KwfsLCd' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sessions/{session}/restart',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@restartSession',
        'controller' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@restartSession',
        'namespace' => NULL,
        'prefix' => 'api/sessions',
        'where' => 
        array (
        ),
        'as' => 'generated::HWpqLFIo0KwfsLCd',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::PcvkMzvnykna0XcU' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sessions/{session}/logout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@logoutSession',
        'controller' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@logoutSession',
        'namespace' => NULL,
        'prefix' => 'api/sessions',
        'where' => 
        array (
        ),
        'as' => 'generated::PcvkMzvnykna0XcU',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::hfxzJ8VKAwMvQcSf' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sessions/{session}/start',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@startSession',
        'controller' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@startSession',
        'namespace' => NULL,
        'prefix' => 'api/sessions',
        'where' => 
        array (
        ),
        'as' => 'generated::hfxzJ8VKAwMvQcSf',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::8OiqWWOmBjXhXEqB' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sendText',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@sendTestMessage',
        'controller' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@sendTestMessage',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::8OiqWWOmBjXhXEqB',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::9QI1axFBnCL2gjpQ' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/categories',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\CategoryController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\CategoryController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::9QI1axFBnCL2gjpQ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::LyuUByZmSmWRwr3h' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/category/{category}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\CategoryController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\CategoryController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::LyuUByZmSmWRwr3h',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
        'category' => 'slug',
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::WWh6OeYRSzxAF37d' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/brands',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\BrandController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\BrandController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::WWh6OeYRSzxAF37d',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::8rNGnI7pvFUT1sGM' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/brand/{brand}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\BrandController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\BrandController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::8rNGnI7pvFUT1sGM',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
        'brand' => 'slug',
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::aBKLvlg2mNLrgGnX' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/brands-premiere',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\BrandController@getPremiereBrands',
        'controller' => 'App\\Http\\Controllers\\Api\\BrandController@getPremiereBrands',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::aBKLvlg2mNLrgGnX',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::nK9BI3G7JzVDaTJb' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/products',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\ProductController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\ProductController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::nK9BI3G7JzVDaTJb',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::DTjISVWB95RakSX5' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/product/{product}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\ProductController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\ProductController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::DTjISVWB95RakSX5',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
        'product' => 'slug',
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::YRNeoHhPga5Oqf6u' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/BrowseProduct',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\ProductController@ProductsHome',
        'controller' => 'App\\Http\\Controllers\\Api\\ProductController@ProductsHome',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::YRNeoHhPga5Oqf6u',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::jf1kE6ubmbwdorwH' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/bundlings',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\BundlingController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\BundlingController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::jf1kE6ubmbwdorwH',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::VSq7HuJMdKCFTqNM' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/bundling/{bundling}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\BundlingController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\BundlingController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::VSq7HuJMdKCFTqNM',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
        'bundling' => 'slug',
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::9OcRnYkhuvEIXhLg' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/sub-categories',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\SubCategoryController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\SubCategoryController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::9OcRnYkhuvEIXhLg',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::WWQyIEah89NkoOyt' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/sub-categories/{subCategory}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\SubCategoryController@show',
        'controller' => 'App\\Http\\Controllers\\Api\\SubCategoryController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::WWQyIEah89NkoOyt',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
        'subCategory' => 'slug',
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::7nZtRRp8MWA4Vp2V' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/search-suggestions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\ProductController@searchSuggestions',
        'controller' => 'App\\Http\\Controllers\\Api\\ProductController@searchSuggestions',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::7nZtRRp8MWA4Vp2V',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::J5dENcvS4s6almO8' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/transactions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\TransactionController@index',
        'controller' => 'App\\Http\\Controllers\\Api\\TransactionController@index',
        'namespace' => NULL,
        'prefix' => 'api/transactions',
        'where' => 
        array (
        ),
        'as' => 'generated::J5dENcvS4s6almO8',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::6GLuNNuWHHKZYCo2' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/transactions/check-availability',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\TransactionController@checkAvailability',
        'controller' => 'App\\Http\\Controllers\\Api\\TransactionController@checkAvailability',
        'namespace' => NULL,
        'prefix' => 'api/transactions',
        'where' => 
        array (
        ),
        'as' => 'generated::6GLuNNuWHHKZYCo2',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::xN59tvAMXK3FX0mU' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/search',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\AdvancedSearchController@search',
        'controller' => 'App\\Http\\Controllers\\Api\\AdvancedSearchController@search',
        'namespace' => NULL,
        'prefix' => 'api/search',
        'where' => 
        array (
        ),
        'as' => 'generated::xN59tvAMXK3FX0mU',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Gna5OoIfrH2hJk6W' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/search/autocomplete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:180,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\AdvancedSearchController@autocomplete',
        'controller' => 'App\\Http\\Controllers\\Api\\AdvancedSearchController@autocomplete',
        'namespace' => NULL,
        'prefix' => 'api/search',
        'where' => 
        array (
        ),
        'as' => 'generated::Gna5OoIfrH2hJk6W',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::c4aEVq1QCTOmDtvV' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/search/popular',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\AdvancedSearchController@popularSuggestions',
        'controller' => 'App\\Http\\Controllers\\Api\\AdvancedSearchController@popularSuggestions',
        'namespace' => NULL,
        'prefix' => 'api/search',
        'where' => 
        array (
        ),
        'as' => 'generated::c4aEVq1QCTOmDtvV',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::hDhj40aLriTxk0jz' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/search/stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:30,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\AdvancedSearchController@getSearchStats',
        'controller' => 'App\\Http\\Controllers\\Api\\AdvancedSearchController@getSearchStats',
        'namespace' => NULL,
        'prefix' => 'api/search',
        'where' => 
        array (
        ),
        'as' => 'generated::hDhj40aLriTxk0jz',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::vO7IczRG05c6OCLG' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/availability/check',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\AvailabilityController@check',
        'controller' => 'App\\Http\\Controllers\\Api\\AvailabilityController@check',
        'namespace' => NULL,
        'prefix' => 'api/availability',
        'where' => 
        array (
        ),
        'as' => 'generated::vO7IczRG05c6OCLG',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::nl74WB1pktnRvo8F' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/availability/check-multiple',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\AvailabilityController@checkMultiple',
        'controller' => 'App\\Http\\Controllers\\Api\\AvailabilityController@checkMultiple',
        'namespace' => NULL,
        'prefix' => 'api/availability',
        'where' => 
        array (
        ),
        'as' => 'generated::nl74WB1pktnRvo8F',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::JC51MN17spUWRsZf' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/availability/unavailable-dates',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:180,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\AvailabilityController@getUnavailableDates',
        'controller' => 'App\\Http\\Controllers\\Api\\AvailabilityController@getUnavailableDates',
        'namespace' => NULL,
        'prefix' => 'api/availability',
        'where' => 
        array (
        ),
        'as' => 'generated::JC51MN17spUWRsZf',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::66JBzFfVcykFONOv' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/availability/check-range',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:120,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\AvailabilityController@isDateRangeAvailable',
        'controller' => 'App\\Http\\Controllers\\Api\\AvailabilityController@isDateRangeAvailable',
        'namespace' => NULL,
        'prefix' => 'api/availability',
        'where' => 
        array (
        ),
        'as' => 'generated::66JBzFfVcykFONOv',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::f1BKqpvbW091ZWHD' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/availability/check-cart',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:30,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\AvailabilityController@checkCartAvailability',
        'controller' => 'App\\Http\\Controllers\\Api\\AvailabilityController@checkCartAvailability',
        'namespace' => NULL,
        'prefix' => 'api/availability',
        'where' => 
        array (
        ),
        'as' => 'generated::f1BKqpvbW091ZWHD',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::BbWQC26GCMLPXjY4' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/availability/stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'throttle:120,1',
          2 => 'throttle:30,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\AvailabilityController@getStats',
        'controller' => 'App\\Http\\Controllers\\Api\\AvailabilityController@getStats',
        'namespace' => NULL,
        'prefix' => 'api/availability',
        'where' => 
        array (
        ),
        'as' => 'generated::BbWQC26GCMLPXjY4',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::fitgGkw88jxyCKvg' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/categories',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\CategoryController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\CategoryController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::fitgGkw88jxyCKvg',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::iaddcvOpZyQkCGGq' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/categories/{category}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\CategoryController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\CategoryController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::iaddcvOpZyQkCGGq',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::9qm4hYe4RQKak5eR' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/categories/{category}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\CategoryController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\CategoryController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::9qm4hYe4RQKak5eR',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::lGLOUUqcpDBUZ1Wh' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/brands',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\BrandController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\BrandController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::lGLOUUqcpDBUZ1Wh',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::XzSrsBKpv05E06Rz' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/brands/{brand}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\BrandController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\BrandController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::XzSrsBKpv05E06Rz',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::7cMf8VpKQRecWz56' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/brands/{brand}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\BrandController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\BrandController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::7cMf8VpKQRecWz56',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::nEeFFCm7N5nwChEi' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/products',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\ProductController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\ProductController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::nEeFFCm7N5nwChEi',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::LoM3IsKLuuwCIdWg' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/products/{product}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\ProductController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\ProductController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::LoM3IsKLuuwCIdWg',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::plaJBXteGTvqSIZr' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/products/{product}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\ProductController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\ProductController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::plaJBXteGTvqSIZr',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::4ojjSdJxg4HymDIm' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/bundlings',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\BundlingController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\BundlingController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::4ojjSdJxg4HymDIm',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::4mIHme0CH9Nv5cya' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/bundlings/{bundling}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\BundlingController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\BundlingController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::4mIHme0CH9Nv5cya',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::HYlzzgZbxTgW9VLC' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/bundlings/{bundling}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\BundlingController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\BundlingController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::HYlzzgZbxTgW9VLC',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::dx2auV63bsRtcBAq' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/sub-categories',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\SubCategoryController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\SubCategoryController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::dx2auV63bsRtcBAq',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::0OlJLdiVajyRQxOA' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/sub-categories/{sub_category}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\SubCategoryController@update',
        'controller' => 'App\\Http\\Controllers\\Api\\SubCategoryController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::0OlJLdiVajyRQxOA',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::mrHzeStq1lrYYK1P' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/sub-categories/{sub_category}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\SubCategoryController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api\\SubCategoryController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::mrHzeStq1lrYYK1P',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::hz8dVZPVFdESAFLX' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/google-sheet-sync',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\GoogleSheetSyncController@sync',
        'controller' => 'App\\Http\\Controllers\\GoogleSheetSyncController@sync',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::hz8dVZPVFdESAFLX',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::77ZuxNvS6f56W3s4' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/google-sheet-export',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\GoogleSheetSyncController@export',
        'controller' => 'App\\Http\\Controllers\\GoogleSheetSyncController@export',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::77ZuxNvS6f56W3s4',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'transactions-check.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/transactions-check',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'as' => 'transactions-check.index',
        'uses' => 'App\\Http\\Controllers\\TransactionCheckController@index',
        'controller' => 'App\\Http\\Controllers\\TransactionCheckController@index',
        'namespace' => NULL,
        'prefix' => 'api/',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'transactions-check.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/transactions-check',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'as' => 'transactions-check.store',
        'uses' => 'App\\Http\\Controllers\\TransactionCheckController@store',
        'controller' => 'App\\Http\\Controllers\\TransactionCheckController@store',
        'namespace' => NULL,
        'prefix' => 'api/',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'transactions-check.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/transactions-check/{transactions_check}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'as' => 'transactions-check.show',
        'uses' => 'App\\Http\\Controllers\\TransactionCheckController@show',
        'controller' => 'App\\Http\\Controllers\\TransactionCheckController@show',
        'namespace' => NULL,
        'prefix' => 'api/',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'transactions-check.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/transactions-check/{transactions_check}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'as' => 'transactions-check.update',
        'uses' => 'App\\Http\\Controllers\\TransactionCheckController@update',
        'controller' => 'App\\Http\\Controllers\\TransactionCheckController@update',
        'namespace' => NULL,
        'prefix' => 'api/',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'transactions-check.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/transactions-check/{transactions_check}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'as' => 'transactions-check.destroy',
        'uses' => 'App\\Http\\Controllers\\TransactionCheckController@destroy',
        'controller' => 'App\\Http\\Controllers\\TransactionCheckController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::875z2NTzzd1nTCVK' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/transaction',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\TransactionController@store',
        'controller' => 'App\\Http\\Controllers\\Api\\TransactionController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::875z2NTzzd1nTCVK',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::wziA9UBKtaSsqzA9' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/check-transaction',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api_key',
          2 => 'throttle:60,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\TransactionController@DetailTransaction',
        'controller' => 'App\\Http\\Controllers\\Api\\TransactionController@DetailTransaction',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::wziA9UBKtaSsqzA9',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::1co0KXJDSEZeVHGV' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/import/status/{importId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\ImportStatusController@getStatus',
        'controller' => 'App\\Http\\Controllers\\Api\\ImportStatusController@getStatus',
        'namespace' => NULL,
        'prefix' => 'api/import',
        'where' => 
        array (
        ),
        'as' => 'generated::1co0KXJDSEZeVHGV',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::M0af4hqSKdKhMf3K' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/import/results/{importId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\ImportStatusController@getResults',
        'controller' => 'App\\Http\\Controllers\\Api\\ImportStatusController@getResults',
        'namespace' => NULL,
        'prefix' => 'api/import',
        'where' => 
        array (
        ),
        'as' => 'generated::M0af4hqSKdKhMf3K',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::bN578xExtcgSoX0Z' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/import/queue-status',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\ImportStatusController@checkQueueStatus',
        'controller' => 'App\\Http\\Controllers\\Api\\ImportStatusController@checkQueueStatus',
        'namespace' => NULL,
        'prefix' => 'api/import',
        'where' => 
        array (
        ),
        'as' => 'generated::bN578xExtcgSoX0Z',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::z5B765MVgYmzxTgi' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'up',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:822:"function () {
                    $exception = null;

                    try {
                        \\Illuminate\\Support\\Facades\\Event::dispatch(new \\Illuminate\\Foundation\\Events\\DiagnosingHealth);
                    } catch (\\Throwable $e) {
                        if (app()->hasDebugModeEnabled()) {
                            throw $e;
                        }

                        report($e);

                        $exception = $e->getMessage();
                    }

                    return response(\\Illuminate\\Support\\Facades\\View::file(\'C:\\\\laragon\\\\www\\\\GPR\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Configuration\'.\'/../resources/health-up.blade.php\', [
                        \'exception\' => $exception,
                    ]), status: $exception ? 500 : 200);
                }";s:5:"scope";s:54:"Illuminate\\Foundation\\Configuration\\ApplicationBuilder";s:4:"this";N;s:4:"self";s:32:"0000000000000fd50000000000000000";}}',
        'as' => 'generated::z5B765MVgYmzxTgi',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::TIsWwcUU8qB1SJza' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '/',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:118:"function () {
    \\Illuminate\\Support\\Facades\\Log::info(\'Test log entry from web.php\');
    return \\view(\'welcome\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000010190000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::TIsWwcUU8qB1SJza',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::7VD1JptuWwO9gZH2' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'test-log',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:152:"function () {
    \\Illuminate\\Support\\Facades\\Log::info(\'Test log entry from /test-log route.\');
    return \'Log entry written from /test-log route.\';
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000101b0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::7VD1JptuWwO9gZH2',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::3LX0yG9GWNqNvXGc' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => '/',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\SessionController@login',
        'controller' => 'App\\Http\\Controllers\\SessionController@login',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::3LX0yG9GWNqNvXGc',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'forgot.password' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'forgot-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\SessionController@showForgotPasswordForm',
        'controller' => 'App\\Http\\Controllers\\SessionController@showForgotPasswordForm',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'forgot.password',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'reset.password' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'forgot-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\SessionController@resetPassword',
        'controller' => 'App\\Http\\Controllers\\SessionController@resetPassword',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'reset.password',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pdf' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'pdf/{order}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\PdfController@__invoke',
        'controller' => 'App\\Http\\Controllers\\PdfController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'pdf',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'google.callback' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'auth/google/callback',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\GoogleSheetSyncController@handleCallback',
        'controller' => 'App\\Http\\Controllers\\GoogleSheetSyncController@handleCallback',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'google.callback',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sync' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sync',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\GoogleSheetSyncController@sync',
        'controller' => 'App\\Http\\Controllers\\GoogleSheetSyncController@sync',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'sync',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registration.form' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'register',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistrationController@showForm',
        'controller' => 'App\\Http\\Controllers\\RegistrationController@showForm',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registration.form',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registration.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'register',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistrationController@register',
        'controller' => 'App\\Http\\Controllers\\RegistrationController@register',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registration.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registration.success' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'registration/success',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistrationController@success',
        'controller' => 'App\\Http\\Controllers\\RegistrationController@success',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registration.success',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'verification.verify' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'email/verify/{id}/{hash}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'signed',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistrationController@verifyEmail',
        'controller' => 'App\\Http\\Controllers\\RegistrationController@verifyEmail',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'verification.verify',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'product.search' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'search-products',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\ProductSearchController@index',
        'controller' => 'App\\Http\\Controllers\\ProductSearchController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'product.search',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.check-email' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/check-email',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Api\\EmailCheckController@checkEmail',
        'controller' => 'App\\Http\\Controllers\\Api\\EmailCheckController@checkEmail',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.check-email',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::VkuAH726OmZbRgbx' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin-login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:47:"function () {
    return \\redirect(\'/admin\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000010290000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::VkuAH726OmZbRgbx',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'customers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@index',
        'controller' => 'App\\Http\\Controllers\\CustomerController@index',
        'as' => 'customers.index',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'customers/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@create',
        'controller' => 'App\\Http\\Controllers\\CustomerController@create',
        'as' => 'customers.create',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'customers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@store',
        'controller' => 'App\\Http\\Controllers\\CustomerController@store',
        'as' => 'customers.store',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'customers/{customer}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@show',
        'controller' => 'App\\Http\\Controllers\\CustomerController@show',
        'as' => 'customers.show',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'customers/{customer}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@edit',
        'controller' => 'App\\Http\\Controllers\\CustomerController@edit',
        'as' => 'customers.edit',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'customers/{customer}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@update',
        'controller' => 'App\\Http\\Controllers\\CustomerController@update',
        'as' => 'customers.update',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'customers/{customer}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@destroy',
        'controller' => 'App\\Http\\Controllers\\CustomerController@destroy',
        'as' => 'customers.destroy',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.import.form' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'customers/import/form',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@importForm',
        'controller' => 'App\\Http\\Controllers\\CustomerController@importForm',
        'as' => 'customers.import.form',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.import' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'customers/import',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@import',
        'controller' => 'App\\Http\\Controllers\\CustomerController@import',
        'as' => 'customers.import',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.export' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'customers/export',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@export',
        'controller' => 'App\\Http\\Controllers\\CustomerController@export',
        'as' => 'customers.export',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.import.template' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'customers/import/template',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@downloadTemplate',
        'controller' => 'App\\Http\\Controllers\\CustomerController@downloadTemplate',
        'as' => 'customers.import.template',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.bulk-action' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'customers/bulk-action',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@bulkAction',
        'controller' => 'App\\Http\\Controllers\\CustomerController@bulkAction',
        'as' => 'customers.bulk-action',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.bulk-action.start' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'customers/bulk-action/start',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\BulkActionProgressController@startBulkAction',
        'controller' => 'App\\Http\\Controllers\\BulkActionProgressController@startBulkAction',
        'as' => 'customers.bulk-action.start',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.bulk-action.progress' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'customers/bulk-action/progress',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\BulkActionProgressController@getProgress',
        'controller' => 'App\\Http\\Controllers\\BulkActionProgressController@getProgress',
        'as' => 'customers.bulk-action.progress',
        'namespace' => NULL,
        'prefix' => '/customers',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'whatsapp.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'whatsapp',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'whatsapp.auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:76:"function () {
        return \\redirect()->route(\'whatsapp.dashboard\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000103b0000000000000000";}}',
        'as' => 'whatsapp.index',
        'namespace' => NULL,
        'prefix' => '/whatsapp',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'whatsapp.login.form' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'whatsapp/login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:65:"function () {
        return \\view(\'admin.whatsapp.login\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000103d0000000000000000";}}',
        'as' => 'whatsapp.login.form',
        'namespace' => NULL,
        'prefix' => '/whatsapp',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'whatsapp.login' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'whatsapp/login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:463:"function (\\Illuminate\\Http\\Request $request) {
        $username = $request->input(\'username\');
        $password = $request->input(\'password\');

        if ($username === \'wahaadmin\' && $password === \'Infrasglobal@100\') {
            $request->session()->put(\'whatsapp_authenticated\', true);
            return \\redirect()->route(\'whatsapp.dashboard\');
        } else {
            return \\redirect()->back()->withErrors([\'Invalid credentials\']);
        }
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000103f0000000000000000";}}',
        'as' => 'whatsapp.login',
        'namespace' => NULL,
        'prefix' => '/whatsapp',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'whatsapp.auth.logout' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'whatsapp/auth-logout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:172:"function (\\Illuminate\\Http\\Request $request) {
        $request->session()->forget(\'whatsapp_authenticated\');
        return \\redirect()->route(\'whatsapp.dashboard\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000010410000000000000000";}}',
        'as' => 'whatsapp.auth.logout',
        'namespace' => NULL,
        'prefix' => '/whatsapp',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'whatsapp.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'whatsapp/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'whatsapp.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@dashboard',
        'controller' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@dashboard',
        'as' => 'whatsapp.dashboard',
        'namespace' => NULL,
        'prefix' => '/whatsapp',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'whatsapp.status' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'whatsapp/status',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'whatsapp.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@getSessionStatus',
        'controller' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@getSessionStatus',
        'as' => 'whatsapp.status',
        'namespace' => NULL,
        'prefix' => '/whatsapp',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'whatsapp.qr' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'whatsapp/qr',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'whatsapp.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@getQrCode',
        'controller' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@getQrCode',
        'as' => 'whatsapp.qr',
        'namespace' => NULL,
        'prefix' => '/whatsapp',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'whatsapp.test' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'whatsapp/test',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'whatsapp.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@sendTestMessage',
        'controller' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@sendTestMessage',
        'as' => 'whatsapp.test',
        'namespace' => NULL,
        'prefix' => '/whatsapp',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'whatsapp.restart' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'whatsapp/restart',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'whatsapp.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@restartSession',
        'controller' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@restartSession',
        'as' => 'whatsapp.restart',
        'namespace' => NULL,
        'prefix' => '/whatsapp',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'whatsapp.logout' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'whatsapp/logout-session',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'whatsapp.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@logoutSession',
        'controller' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@logoutSession',
        'as' => 'whatsapp.logout',
        'namespace' => NULL,
        'prefix' => '/whatsapp',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'whatsapp.logs' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'whatsapp/logs',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'whatsapp.auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@getSessionLogs',
        'controller' => 'App\\Http\\Controllers\\Admin\\WhatsAppController@getSessionLogs',
        'as' => 'whatsapp.logs',
        'namespace' => NULL,
        'prefix' => '/whatsapp',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'failed-import.download' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'failed-import/download',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\FailedImportDownloadController@download',
        'controller' => 'App\\Http\\Controllers\\FailedImportDownloadController@download',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'failed-import.download',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'failed-import.clear' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'failed-import/clear',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\FailedImportDownloadController@clearNotification',
        'controller' => 'App\\Http\\Controllers\\FailedImportDownloadController@clearNotification',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'failed-import.clear',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'import.download-failed' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'import/download-failed',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\ImportErrorController@downloadFailedRows',
        'controller' => 'App\\Http\\Controllers\\ImportErrorController@downloadFailedRows',
        'as' => 'import.download-failed',
        'namespace' => NULL,
        'prefix' => '/import',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'import.view-errors' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'import/view-errors',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\ImportErrorController@viewAllErrors',
        'controller' => 'App\\Http\\Controllers\\ImportErrorController@viewAllErrors',
        'as' => 'import.view-errors',
        'namespace' => NULL,
        'prefix' => '/import',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'import.statistics' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'import/statistics',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\ImportErrorController@getImportStatistics',
        'controller' => 'App\\Http\\Controllers\\ImportErrorController@getImportStatistics',
        'as' => 'import.statistics',
        'namespace' => NULL,
        'prefix' => '/import',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'import.failed-rows' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'import/failed-rows',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\ImportErrorController@getFailedRowsPaginated',
        'controller' => 'App\\Http\\Controllers\\ImportErrorController@getFailedRowsPaginated',
        'as' => 'import.failed-rows',
        'namespace' => NULL,
        'prefix' => '/import',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'import.clear-failed-data' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'import/clear-failed-data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\ImportErrorController@clearFailedImportData',
        'controller' => 'App\\Http\\Controllers\\ImportErrorController@clearFailedImportData',
        'as' => 'import.clear-failed-data',
        'namespace' => NULL,
        'prefix' => '/import',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'import.reprocess-failed' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'import/reprocess-failed',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\ImportErrorController@reprocessFailedRows',
        'controller' => 'App\\Http\\Controllers\\ImportErrorController@reprocessFailedRows',
        'as' => 'import.reprocess-failed',
        'namespace' => NULL,
        'prefix' => '/import',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.local' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'storage/{path}',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:3:{s:4:"disk";s:5:"local";s:6:"config";a:4:{s:6:"driver";s:5:"local";s:4:"root";s:38:"C:\\laragon\\www\\GPR\\storage\\app/private";s:5:"serve";b:1;s:5:"throw";b:0;}s:12:"isProduction";b:0;}s:8:"function";s:323:"function (\\Illuminate\\Http\\Request $request, string $path) use ($disk, $config, $isProduction) {
                    return (new \\Illuminate\\Filesystem\\ServeFile(
                        $disk,
                        $config,
                        $isProduction
                    ))($request, $path);
                }";s:5:"scope";s:47:"Illuminate\\Filesystem\\FilesystemServiceProvider";s:4:"this";N;s:4:"self";s:32:"00000000000010440000000000000000";}}',
        'as' => 'storage.local',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
  ),
)
);
