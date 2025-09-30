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
    /* Simple fade + slide animation */
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

<body class="min-h-screen flex items-center justify-center bg-orange-900 relative">

  <!-- Background Image -->
  <div class="absolute inset-0">
    <img src="{{ asset('images/login.jpg') }}" class="w-full h-full object-cover" alt="Background" />
    <div class="absolute inset-0 bg-black bg-opacity-50"></div> <!-- Dark overlay for readability -->
  </div>

  <!-- Centered Login Form -->
  <div class="relative z-10 w-full max-w-sm bg-white rounded-2xl shadow-xl p-8 animate-fadeSlideUp">
    
<!-- Header Image (overlapping circle) -->
<div class="absolute -top-10 left-1/2 transform -translate-x-1/2">
  <div class="w-24 h-24 bg-white rounded-full shadow-lg flex items-center justify-center overflow-hidden">
    <img src="{{ asset('images/pets2go.png') }}" 
         alt="Header Logo" 
         class="w-full h-full object-cover" />
  </div>
</div>

<br><br>
<div>
    <h2 class="text-center font-bold text-xl text-gray-800 mb-2">Sign In</h2>
    <p class="text-center text-xs text-gray-500 mb-6">Login using your registered credentials</p>
</div>
    {{-- Success Message --}}
    @if(session('success'))
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
        {{ session('success') }}
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
      @csrf

      <!-- Email -->
      <div>
        <label for="email" class="block text-[10px] font-semibold text-gray-600 mb-1 uppercase">Email Address</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 text-xs">
            <i class="fas fa-user"></i>
          </span>
          <input name="user_email" id="email" type="email" placeholder="name@example.com" required autofocus
            class="w-full pl-8 pr-3 py-2 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
        </div>
      </div>

      <!-- Password -->
      <div>
        <label for="password" class="block text-[10px] font-semibold text-gray-600 mb-1 uppercase">Password</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 text-xs">
            <i class="fas fa-key"></i>
          </span>
          <input name="user_password" id="password" type="password" placeholder="Password" required
            class="w-full pl-8 pr-3 py-2 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
        </div>
      </div>

      <!-- Remember + Forgot -->
      <div class="flex items-center justify-between text-xs text-gray-600">
        <label class="flex items-center space-x-2">
          <input type="checkbox" name="remember"
            class="w-3 h-3 text-blue-600 border border-gray-300 rounded focus:ring-blue-500">
          <span>Remember me</span>
        </label>
        <a class="text-blue-600 hover:underline" href="{{ route('password.reset') }}">
          Forgot Password?
        </a>
      </div>

      <!-- Submit -->
      <button type="submit"
        class="w-full bg-pink-400 text-white text-xs font-semibold py-2 rounded mt-4 hover:bg-pink-700 transition">
        Log In
      </button>

      <!-- Errors -->
      @if ($errors->any())
        <div class="text-red-600 text-xs mt-2">
          @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
          @endforeach
        </div>
      @endif
    </form>

    <!-- Footer -->
    <p class="text-center text-[10px] text-blue-600 font-semibold mt-4">
      Don’t have an Account? Contact your Administrator
    </p>
  </div>

</body>
</html>
