<x-layout>
    <form action="{{ route('login') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-1">Adresse Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                placeholder="admin@exemple.com" required autofocus>
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-1">Mot de passe</label>
            <input type="password" name="password"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                placeholder="••••••••" required>
        </div>

        <div class="flex items-center">
            <input type="checkbox" name="remember" id="remember"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
            <label for="remember" class="ml-2 block text-sm text-gray-600">Se souvenir de moi</label>
        </div>

        <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 shadow-md hover:shadow-lg">
            Se connecter
        </button>
    </form>
</x-layout>