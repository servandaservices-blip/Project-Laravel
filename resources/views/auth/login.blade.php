<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SERVANDA</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gradient-to-br from-[#0B2E4F] via-[#2F80ED] to-[#27AE60] flex items-center justify-center px-4 py-6 relative overflow-hidden">

    <svg class="absolute bottom-0 left-0 w-full opacity-20" viewBox="0 0 1440 320" aria-hidden="true">
        <path fill="#ffffff" fill-opacity="0.2"
            d="M0,192L80,202.7C160,213,320,235,480,213.3C640,192,800,128,960,122.7C1120,117,1280,171,1360,197.3L1440,224V320H0Z">
        </path>
    </svg>

    <div class="absolute inset-0 opacity-10"
        style="background-image: linear-gradient(white 1px, transparent 1px),
               linear-gradient(90deg, white 1px, transparent 1px);
               background-size: 40px 40px;">
    </div>

    <div class="absolute w-2 h-2 bg-white rounded-full top-20 left-20 animate-ping"></div>
    <div class="absolute w-2 h-2 bg-white rounded-full top-40 right-40 animate-ping"></div>
    <div class="absolute w-2 h-2 bg-white rounded-full bottom-40 left-1/3 animate-ping"></div>

    <div class="absolute w-96 h-96 bg-blue-400 opacity-20 rounded-full blur-3xl -top-20 -left-20 animate-pulse"></div>
    <div class="absolute w-80 h-80 bg-green-400 opacity-20 rounded-full blur-3xl bottom-0 right-0 animate-pulse"></div>

    <div class="bg-white rounded-[28px] shadow-2xl w-full max-w-[440px] p-5 md:p-6 lg:p-7 relative z-10 mx-auto">

        <div class="text-center mb-4">
            <img src="/logo.png" alt="SERVANDA Logo" class="w-20 md:w-24 mx-auto mb-2">
        </div>

        <div class="text-center mb-5">
            <h1 class="text-[1.65rem] md:text-[1.9rem] leading-tight font-bold tracking-tight text-[#0B2E4F]">SERVANDA SERVICES</h1>
            <p class="text-gray-600 text-[10px] md:text-[11px] mt-1.5 tracking-[0.24em] uppercase font-semibold">
                Business Management & Analysis System
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                Username atau password salah.
            </div>
        @endif

        <form method="POST" action="{{ route('login.process') }}">
            @csrf

            <div class="mb-3.5">
                <label for="username" class="block text-gray-500 text-sm font-semibold mb-1.5 tracking-wide">
                    USERNAME
                </label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="{{ old('username') }}"
                    class="w-full px-4 py-3 text-base border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-400 @error('username') border-red-400 @enderror"
                    placeholder="Masukkan username"
                    required
                    autofocus
                >
                @error('username')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-500 text-sm font-semibold mb-1.5 tracking-wide">
                    PASSWORD
                </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="w-full px-4 py-3 text-base border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-400 @error('password') border-red-400 @enderror"
                    placeholder="Masukkan password"
                    required
                >
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full bg-[#2F80ED] text-white py-3 text-base rounded-2xl hover:bg-blue-600 transition font-semibold shadow-md">
                Login
            </button>
        </form>

    </div>

</body>
</html>
