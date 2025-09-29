<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title>Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <style>
        input::placeholder {
            color: #a0aec0;
        }
    </style>
</head>

<body class="min-h-screen flex">

    <!-- Left Side Image -->
    <div class="hidden md:block md:w-1/2 relative">
        <img alt="Logo" src="{{ asset('images/mbvcmsLogo.png') }}" class="absolute top-0 left-0 w-[400px] z-10" />
        <img src="{{ asset('images/fQkuD2n.jpg') }}" class="w-full h-full object-cover" alt="Background" />
    </div>

    <!-- Right Side Login Form -->
    <div class="flex flex-1 flex-col justify-center items-center bg-[#f3f7f8] px-6 py-12 md:w-1/2">
        <div class="w-full max-w-sm">
            <h2 class="text-center font-semibold text-lg text-[#1a202c] mb-1">Sign In</h2>
            <p class="text-center text-xs text-[#4a5568] mb-8">Login using your registered credentials</p>

             {{-- Success Message --}}
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
      {{ session('success') }}
    </div>
    @endif
            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-[10px] font-semibold text-[#4a5568] mb-1 uppercase">
                        Email Address
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-[#718096] text-xs">
                            <i class="fas fa-user"></i>
                        </span>
                        <input name="user_email" id="email" type="email" placeholder="name@example.com" required autofocus
                            class="w-full pl-8 pr-3 py-2 text-xs border border-[#cbd5e0] rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-[10px] font-semibold text-[#4a5568] mb-1 uppercase">
                        Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-[#718096] text-xs">
                            <i class="fas fa-key"></i>
                        </span>
                        <input name="user_password" id="password" type="password" placeholder="Password" required
                            class="w-full pl-8 pr-3 py-2 text-xs border border-[#cbd5e0] rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <!-- Remember Me & Forgot -->
                <div class="flex items-center justify-between text-xs text-[#4a5568]">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="remember"
                            class="w-3 h-3 text-blue-600 border border-gray-300 rounded focus:ring-blue-500">
                        <span>Remember me</span>
                    </label>
                    <a class="text-blue-600 hover:underline" href="{{ route('password.reset') }}">
                        Forgot Password?
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full bg-blue-600 text-white text-xs font-semibold py-2 rounded mt-4 hover:bg-[#0f7ea0] transition">
                    Log In
                </button>

                <!-- Error Feedback -->
                @if ($errors->any())
                    <div class="text-red-600 text-xs mt-2">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
            </form>

            <!-- Footer Note -->
            <p class="text-center text-[10px] text-blue-600 font-semibold mt-4">
                Don’t have an Account? Contact your Administrator
            </p>
        </div>
    </div>
</body>

</html>