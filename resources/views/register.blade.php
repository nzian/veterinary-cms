<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title>Register</title>
    @vite(['resources/css/app.css'])
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

    <!-- Right Side Register Form -->
    <div class="flex flex-1 flex-col justify-center items-center bg-[#f3f7f8] px-6 py-12 md:w-1/2">
        <div class="w-full max-w-sm">
            <h2 class="text-center font-semibold text-lg text-[#1a202c] mb-1">Register</h2>
            <p class="text-center text-xs text-[#4a5568] mb-8">Create a new account below</p>

            <!-- Register Form -->
            <form method="POST" action="{{ route('register.submit') }}" class="space-y-4">
                @csrf

                <!-- Name -->
                <div>
                    <label for="name" class="block text-[10px] font-semibold text-[#4a5568] mb-1 uppercase">
                        Full Name
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-[#718096] text-xs">
                            <i class="fas fa-user"></i>
                        </span>
                        <input name="user_name" id="name" type="text" placeholder="John Doe" required
                            class="w-full pl-8 pr-3 py-2 text-xs border border-[#cbd5e0] rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-[10px] font-semibold text-[#4a5568] mb-1 uppercase">
                        Email Address
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-[#718096] text-xs">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input name="user_email" id="email" type="email" placeholder="name@example.com" required
                            class="w-full pl-8 pr-3 py-2 text-xs border border-[#cbd5e0] rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <!-- Password -->
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

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-[10px] font-semibold text-[#4a5568] mb-1 uppercase">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-[#718096] text-xs">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input name="user_password_confirmation" id="password_confirmation" type="password"
                            placeholder="Confirm Password" required
                            class="w-full pl-8 pr-3 py-2 text-xs border border-[#cbd5e0] rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <!-- Error Feedback -->
                @if ($errors->any())
                    <div class="text-red-600 text-xs mt-2">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full bg-blue-600 text-white text-xs font-semibold py-2 rounded mt-4 hover:bg-[#0f7ea0] transition">
                    Register
                </button>
            </form>

            <!-- Footer Note -->
            <p class="text-center text-[10px] text-blue-600 font-semibold mt-4">
                Already have an account?
                <a href="{{ route('login') }}" class="hover:underline">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>
