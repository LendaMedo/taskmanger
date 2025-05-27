<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyWorkHub - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 95%;
            width: 420px;
        }
        .login-header {
            background: linear-gradient(135deg, #4a6baf 0%, #234a8b 100%);
            color: white;
            padding: 25px 30px;
        }
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
        .form-input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-input:focus {
            border-color: #4a6baf;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 107, 175, 0.1);
        }
        .form-input.error {
            border-color: #ef4444;
        }
        .error-message {
            color: #ef4444;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        .login-btn {
            background: linear-gradient(135deg, #4a6baf 0%, #234a8b 100%);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 20px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
        .login-btn:hover {
            background: linear-gradient(135deg, #3a5b9f 0%, #133a7b 100%);
            transform: translateY(-1px);
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        .divider:before, .divider:after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #e5e7eb;
        }
        .divider-text {
            padding: 0 15px;
            color: #6b7280;
        }
        .footer-text {
            color: #6b7280;
            font-size: 14px;
        }
        .footer-link {
            color: #4a6baf;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .footer-link:hover {
            color: #234a8b;
            text-decoration: underline;
        }
        .creator-info {
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            margin-top: 25px;
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center p-4">
    <div class="login-container my-10">
        <div class="login-header">
            <h1 class="text-2xl font-semibold mb-1">MyWorkHub</h1>
            <p class="text-sm opacity-80">Welcome back! Login to your account</p>
        </div>
        
        <div class="p-8">
            <form id="loginForm" action="dashboard.php" method="post">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="text" id="email" name="email" class="form-input" placeholder="Email or Username" required>
                    <p class="error-message" id="emailError">Please enter a valid email or username</p>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Password" required>
                    <p class="error-message" id="passwordError">Password must be at least 6 characters</p>
                </div>
                
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="h-4 w-4 accent-blue-600">
                        <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                    </div>
                    <a href="#" class="text-sm text-blue-600 hover:underline">Forgot password?</a>
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
            </form>
            
            <div class="divider">
                <span class="divider-text">or</span>
            </div>
            
            <div class="flex gap-4 justify-center mb-6">
                <button class="flex items-center justify-center bg-blue-600 text-white p-2 rounded-full w-10 h-10 hover:bg-blue-700 transition">
                    <i class="fab fa-facebook-f"></i>
                </button>
                <button class="flex items-center justify-center bg-red-600 text-white p-2 rounded-full w-10 h-10 hover:bg-red-700 transition">
                    <i class="fab fa-google"></i>
                </button>
                <button class="flex items-center justify-center bg-black text-white p-2 rounded-full w-10 h-10 hover:bg-gray-800 transition">
                    <i class="fab fa-apple"></i>
                </button>
            </div>
            
            <p class="footer-text text-center">
                Don't have an account? <a href="#" class="footer-link">Register now</a>
            </p>
        </div>
    </div>
    
    <div class="creator-info">
        Created by Dr. Ahmed AL-sadi &copy; 2023. All rights reserved.
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            let isValid = true;
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');
            
            // Reset error states
            email.classList.remove('error');
            password.classList.remove('error');
            emailError.style.display = 'none';
            passwordError.style.display = 'none';
            
            // Validate email/username
            if (email.value.trim() === '') {
                email.classList.add('error');
                emailError.style.display = 'block';
                isValid = false;
            }
            
            // Validate password
            if (password.value.length < 6) {
                password.classList.add('error');
                passwordError.style.display = 'block';
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>
