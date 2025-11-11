@inject('router', '\Core\Service\RoutingService')
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
        <link rel="stylesheet" href="{{ $router->generateUrl('Admin.assets', ['sType' => 'interface', 'sFile' => 'css/app.css']) }}?v={{ $version }}" />
    </head>
    <body class="font-body">
        <main class="grid min-h-full place-items-center bg-white px-6 py-24 sm:py-32 lg:px-8">
            <div class="text-center">
                <p class="text-xl sm:text-4xl font-semibold text-primary-600">
                    {{ $statusCode }}
                </p>
                <h1 class="mt-4 text-xl lg:text-4xl font-semibold tracking-tight text-balance text-gray-900">
                    {{ $l10n['interface.failed.title'] }}
                </h1>
                <p class="mt-6 text-base font-medium text-pretty text-gray-500">
                    {{ $l10n['interface.failed.text'] }}
                </p>
                @if ($debug)
                    <pre class="text-xs max-h-96 max-w-full mt-4 p-4 text-left bg-gray-100 rounded-xl overflow-auto hidden lg:block">{{ print_r($payload, true)  }}</pre>
                @endif
            </div>
        </main>
    </body>
</html>