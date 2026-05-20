<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
    <title>Sync Lodgify TTlock - Code</title>
</head>

<body>
    <nav>
        @auth
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit"
                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                Se déconnecter
            </button>
        </form>
        @endauth
    </nav>
    <main>
        {{ $slot }}
    </main>
</body>

</html>