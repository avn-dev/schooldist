<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Fidelo Form Widget</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
		body {
			margin: 0;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
			font-size: 1rem;
			font-weight: 400;
			line-height: 1.5;
			color: #212529;
			text-align: left;
			background-color: #fff;
		}
		.container {
			width: 100%;
			margin-right: auto;
			margin-left: auto;
		}
		@media (min-width: 576px) {
			.container {
				max-width: 540px;
			}
		}
		@media (min-width: 768px) {
			.container {
				max-width: 720px;
			}
		}
		@media (min-width: 992px) {
			.container {
				max-width: 960px;
			}
		}
		@media (min-width: 1200px) {
			.container {
				max-width: 1140px;
			}
		}
        .logo img {
            margin: 10px 15px;
            height: 80px;
		}
    </style>
</head>
<body>
<div class="container">
    <a href="{$url}&json" target="_blank" class="logo">
        <img src="https://fidelo.com/resources/images/fidelo_logo_2farbig.svg" alt="Fidelo Software">
    </a>
    <noscript>Please enable JavaScript to continue with this form.</noscript>
    <fidelo-widget></fidelo-widget>
    <script src="{$url}"></script>
</div>
</body>
</html>
