<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WhatsApp Dashboard Login - Global Photo Rental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .whatsapp-green {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .input-focus:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 211, 102, 0.2);
        }
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.3);
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .slide-in {
            animation: slideIn 0.6s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <!-- Background Pattern -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-4 -right-4 w-72 h-72 bg-white opacity-5 rounded-full"></div>
        <div class="absolute top-1/4 -left-8 w-96 h-96 bg-white opacity-3 rounded-full"></div>
        <div class="absolute bottom-1/4 right-1/4 w-48 h-48 bg-white opacity-4 rounded-full"></div>
    </div>

    <div class="w-full max-w-md slide-in">
        <!-- Main Login Card -->
        <div class="glass-effect rounded-2xl shadow-2xl p-8 space-y-6">
            <!-- Header Section -->
            <div class="text-center">
                <!-- Logo -->
                <div class="mx-auto w-16 h-16 whatsapp-green rounded-2xl flex items-center justify-center shadow-lg floating mb-6">
                    <i class="fab fa-whatsapp text-3xl text-white"></i>
                </div>
                
                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    WhatsApp Management
                </h1>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Secure access to your WhatsApp dashboard
                    <br>
                    <span class="text-xs text-gray-500">Global Photo Rental</span>
                </p>
            </div>

            <!-- Error Message -->
            @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-400 mr-2"></i>
                    <span class="text-red-700 text-sm font-medium">{{ $errors->first() }}</span>
                </div>
            </div>
            @endif

            <!-- Login Form -->
            <form method="POST" action="{{ route('whatsapp.login') }}" class="space-y-5">
                @csrf
                
                <!-- Username Field -->
                <div class="space-y-2">
                    <label for="username" class="block text-sm font-medium text-gray-700 ml-1">
                        <i class="fas fa-user mr-2 text-gray-400"></i>Username
                    </label>
                    <input id="username" 
                           name="username" 
                           type="text" 
                           required 
                           value="{{ old('username') }}"
                           placeholder="Enter your username"
                           class="input-focus w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300 text-gray-700 placeholder-gray-400 bg-gray-50 focus:bg-white">
                </div>

                <!-- Password Field -->
                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium text-gray-700 ml-1">
                        <i class="fas fa-lock mr-2 text-gray-400"></i>Password
                    </label>
                    <div class="relative">
                        <input id="password" 
                               name="password" 
                               type="password" 
                               required 
                               placeholder="Enter your password"
                               class="input-focus w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-400 focus:border-transparent transition-all duration-300 text-gray-700 placeholder-gray-400 bg-gray-50 focus:bg-white">
                        <button type="button" 
                                onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors duration-200">
                            <i id="toggleIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Login Button -->
                <button type="submit" 
                        class="btn-hover w-full whatsapp-green text-white py-3 px-4 rounded-xl font-semibold text-lg shadow-lg transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Sign In to Dashboard
                </button>
            </form>

            <!-- Security Notice -->
            <div class="text-center pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-500 flex items-center justify-center">
                    <i class="fas fa-shield-alt mr-2 text-green-500"></i>
                    Secured with authentication
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-white text-sm opacity-75">
                © {{ date('Y') }} Global Photo Rental
            </p>
            <p class="text-white text-xs opacity-60 mt-1">
                Professional photography equipment rental
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        // Auto focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Add loading state to form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing In...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
