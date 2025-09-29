<html lang="en">
 <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>
   Sign In
  </title>
  <script src="https://cdn.tailwindcss.com">
  </script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <style>
   /* Custom placeholder color for inputs */
    input::placeholder {
      color: #a0aec0;
    }
  </style>
 </head>
 <body class="min-h-screen flex">
  <div class="hidden md:block md:w-1/2">
  <img alt="Logo" src="{{ asset('images/mbvcmsLogo.png') }}" class="absolute top-0 left-0 w-[250px] z-10"/>
  <img src="{{ asset('images/fQkuD2n.jpg') }}" class="w-full h-full object-cover" alt="Background height= 800 width= 600"/>
    </div>

  <div class="flex flex-1 flex-col justify-center items-center bg-[#f3f7f8] px-6 py-12 md:w-1/2">
   <div class="w-full max-w-sm">
    <h2 class="text-center font-semibold text-lg text-[#1a202c] mb-1">
     Sign In
    </h2>
    <p class="text-center text-xs text-[#4a5568] mb-8">
     Login using your registered credentials
    </p>
    <form class="space-y-4">
     <div>
      <label class="block text-[10px] font-semibold text-[#4a5568] mb-1 uppercase" for="email">
       Email Address
      </label>
      <div class="relative">
       <span class="absolute inset-y-0 left-3 flex items-center text-[#718096] text-xs">
        <i class="fas fa-user">
        </i>
       </span>
       <input class="w-full pl-8 pr-3 py-2 text-xs border border-[#cbd5e0] rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" id="email" placeholder="name@example.com" type="email"/>
      </div>
     </div>
     <div>
      <label class="block text-[10px] font-semibold text-[#4a5568] mb-1 uppercase" for="password">
       Password
      </label>
      <div class="relative">
       <span class="absolute inset-y-0 left-3 flex items-center text-[#718096] text-xs">
        <i class="fas fa-key">
        </i>
       </span>
       <input class="w-full pl-8 pr-3 py-2 text-xs border border-[#cbd5e0] rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" id="password" placeholder="Password" type="password"/>
      </div>
     </div>
     <div class="flex items-center justify-between text-xs text-[#4a5568]">
      <label class="flex items-center space-x-2">
       <input class="w-3 h-3 text-blue-600 border border-gray-300 rounded focus:ring-blue-500" type="checkbox"/>
       <span>
        Remember me
       </span>
      </label>
      <a class="text-blue-600 hover:underline" href="#">
       Forgot Password?
      </a>
     </div>
     <button class="w-full bg-blue-600 text-white text-xs font-semibold py-2 rounded mt-4 hover:bg-[#0f7ea0] transition" type="submit">
      Log In
     </button>
    </form>
    <p class="text-center text-[10px] text-blue-600 font-semibold mt-4">
     Dont have an Account? Contact your Administrator
    </p>
    </p>
   </div>
  </div>
 </body>
</html>
