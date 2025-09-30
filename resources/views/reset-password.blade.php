<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1" name="viewport" />
  <title>Reset Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
  <style>
    input::placeholder {
      color: #a0aec0;
    }
    /* Reuse the fade + slide animation */
    @keyframes fadeSlideUp {
      0% {
        opacity: 0;
        transform: translateY(20px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .animate-fadeSlideUp {
      animation: fadeSlideUp 0.8s ease-out forwards;
    }
  </style>
</head>

<body class="min-h-screen flex items-center justify-center bg-gray-900 relative">

  <!-- Background Image -->
  <div class="absolute inset-0">
    <img src="{{ asset('images/login.jpg') }}" class="w-full h-full object-cover" alt="Background" />
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
  </div>

  <!-- Centered Reset Form -->
  <div class="relative z-10 w-full max-w-sm bg-white rounded-2xl shadow-xl p-8 animate-fadeSlideUp">

    <!-- Header Image -->
    <div class="flex justify-center mb-4">
      <img src="{{ asset('images/header2.png') }}" 
           alt="Header Logo" 
           class="h-20 object-contain" />
    </div>

    <h2 class="text-center font-bold text-xl text-gray-800 mb-2">Reset Password</h2>
    <p class="text-center text-xs text-gray-500 mb-6">Enter your email and new password</p>

    @if (session('status'))
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
        {{ session('status') }}
      </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
      @csrf

      <!-- Email -->
      <div>
        <label for="email" class="block text-[10px] font-semibold text-gray-600 mb-1 uppercase">Email Address</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 text-xs">
            <i class="fas fa-user"></i>
          </span>
          <input name="email" id="email" type="email" placeholder="name@example.com" required
            class="w-full pl-8 pr-3 py-2 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
        </div>
      </div>

      <!-- New Password -->
      <div>
        <label for="password" class="block text-[10px] font-semibold text-gray-600 mb-1 uppercase">New Password</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 text-xs">
            <i class="fas fa-key"></i>
          </span>
          <input name="password" id="password" type="password" placeholder="New Password" required
            class="w-full pl-8 pr-3 py-2 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
        </div>
      </div>

      <!-- Confirm Password -->
      <div>
        <label for="password_confirmation" class="block text-[10px] font-semibold text-gray-600 mb-1 uppercase">Confirm Password</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 text-xs">
            <i class="fas fa-key"></i>
          </span>
          <input name="password_confirmation" id="password_confirmation" type="password" placeholder="Confirm Password" required
            class="w-full pl-8 pr-3 py-2 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
        </div>
      </div>

      <!-- Errors -->
      @if ($errors->any())
        <div class="text-red-600 text-xs mt-2">
          @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
          @endforeach
        </div>
      @endif

      <!-- Submit -->
      <button type="submit"
        class="w-full bg-pink-400 text-white text-xs font-semibold py-2 rounded mt-4 hover:bg-pink-700 transition">
        Reset Password
      </button>
    </form>

    <!-- Footer -->
    <p class="text-center text-[10px] text-blue-600 font-semibold mt-4">
      <a href="{{ route('login') }}">Back to Login</a>
    </p>
  </div>

</body>
</html>
