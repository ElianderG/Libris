<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} — {{ config('app.name') }}</title>
        <style>
            body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f7f1e6; color: #2b2118; font-family: Georgia, serif; }
            main { max-width: 28rem; padding: 2rem; }
            a { color: #6b2d2d; }
        </style>
    </head>
    <body>
        <main>
            <p>{{ $code }}</p>
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
            <p><a href="{{ url('/') }}">Voltar ao início</a></p>
        </main>
    </body>
</html>
