@inject('router', '\Core\Service\RoutingService')
@php $version = \System::d('version'); @endphp
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

        <!-- Font Awesome -->
        <link rel="stylesheet" href="{{ $router->generateUrl('Admin.assets', ['sType' => 'fontawesome5', 'sFile' => 'css/all.min.css']) }}?v={{ $version }}">
        <link rel="stylesheet" href="{{ $router->generateUrl('Admin.assets', ['sType' => 'fontawesome5', 'sFile' => 'css/v4-shims.css']) }}?v={{ $version }}">

        <!-- App -->
        <link rel="stylesheet" href="{{ $router->generateUrl('Admin.assets', ['sType' => 'fonts', 'sFile' => 'inter/inter.css']) }}?v={{ $version }}"/>
        <link rel="stylesheet" href="{{ $router->generateUrl('Admin.assets', ['sType' => 'interface', 'sFile' => 'css/tailwind.css']) }}?v={{ $version }}"/>
        <link rel="stylesheet" href="{{ $router->generateUrl('Core.assets', ['sFile' => 'v-calendar/style.css']) }}?v={{ $version }}"/>
        <link rel="stylesheet" href="{{ $router->generateUrl('Admin.assets', ['sType' => 'interface', 'sFile' => 'css/app.css']) }}?v={{ $version }}" />
        <link rel="stylesheet" href="{{ $router->generateUrl('Admin.assets', ['sType' => 'custom', 'sFile' => 'css/custom.css']) }}?v={{ $version }}" />

        @inertiaHead
    </head>
    <body class="font-body">

        @inertia

        {{-- TODO zxcvbn.js entfernen --}}
        <script src="{{ $router->generateUrl('Admin.assets', ['sType' => 'js', 'sFile' => 'zxcvbn.js']) }}?v={{ $version }}"></script>
        <script src="{{ $router->generateUrl('Tinymce.tinymce_resources', ['sFile' => 'tinymce.min.js']) }}?v={{ $version }}"></script>
        <script src="{{ $router->generateUrl('Core.assets', ['sFile' => 'js/vue.js']) }}?v={{ $version }}"></script>
        <script src="{{ $router->generateUrl('Admin.assets', ['sType' => 'interface', 'sFile' => 'js/app.js']) }}?v={{ $version }}"></script>

        @if (!empty($supportChat = $page['props']['interface']['support']['support_chat']))
            <script src="{{ $supportChat }}"></script>
        @endif

        @yield('js')
    </body>
</html>