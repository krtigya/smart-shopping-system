<?php

require_once __DIR__ . '/../config/connection.php';

$username = $password = "";
$errors = [];
$showPassword = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars(trim($_POST['username']));
    $password = $_POST['password'];

    if (empty($username)) {
        $errors['username'] = 'Username or Email is required';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }

    if (empty($errors)) {
        $sql = "SELECT id, username, first_name, last_name, email, password, role FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt === false) {
            die("Prepare failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "ss", $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password'])) {
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'] ?? 'user';

                if ($user['role'] === 'admin') {
                    header('Location: ' . BASE_URL . '/admin/dashboard.php');
                    exit();
                } else {
                    header('Location: ' . BASE_URL . '/index.php');
                    exit();
                }
            } else {
                $errors['login'] = 'Invalid username or password';
            }
        } else {
            $errors['login'] = 'User not found';
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Smart Shopping System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .floating-shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            overflow: hidden;
        }
        .shape {
            position: absolute;
            opacity: 0.15;
            background: #818cf8;
            border-radius: 50%;
        }
        .shape1 { width: 420px; height: 420px; top: -120px; left: -120px; }
        .shape2 { width: 320px; height: 320px; bottom: -60px; right: -60px; }
        .shape3 { width: 200px; height: 200px; top: 40%; right: 10%; }
        .input-focus:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15 !important;
        }
        .form-input {
            transition: all 0.3s ease;
        }
        .form-input:focus {
            background-color: #fdf2f8;
        }
        .btn-primary {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #4f46e5 0%, #4f46e5 100%);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3;
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        .link-hover { position: relative; }
        .link-hover::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #4f46e5;
            transition: width 0.3s ease;
        }
        .link-hover:hover::after { width: 100%; }
        .error-shake { animation: shake 0.5s ease-in-out; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-indigo-50 to-indigo-100 min-h-screen">

    <div class="floating-shapes">
        <div class="shape shape1"></div>
        <div class="shape shape2"></div>
        <div class="shape shape3"></div>
    </div>

    <div class="relative z-10 min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
        <div class="w-full max-w-lg">

            <!-- Brand -->
            <div class="mb-10 text-center sm:text-left">
                <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center gap-2 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow">
                        <span class="text-white font-bold text-lg">AC</span>
                    </div>
                    <span class="text-lg font-bold text-slate-900 group-hover:text-indigo-600 transition">Smart Shopping System</span>
                </a>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-indigo-200">
                <div class="relative px-6 sm:px-8 pt-8 pb-6 bg-gradient-to-br from-indigo-50 to-indigo-50 border-b border-indigo-100">
                    <div class="space-y-2">
                        <h1 class="text-3xl font-bold text-slate-900">Welcome Back</h1>
                        <p class="text-slate-600 text-sm">Sign in to your account to continue shopping</p>
                    </div>
                </div>

                <div class="px-6 sm:px-8 py-8">
                    <?php if (isset($_GET['registered']) && $_GET['registered'] == '1'): ?>
                        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-xl">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <p class="font-semibold text-emerald-900">Account Created</p>
                                    <p class="text-emerald-800 text-sm mt-1">Your account was created successfully. Please sign in.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($errors['login'])): ?>
                        <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-xl error-shake">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <p class="font-semibold text-rose-900">Authentication Failed</p>
                                    <p class="text-rose-800 text-sm mt-1"><?= htmlspecialchars($errors['login']) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="space-y-5">
                        <div class="space-y-2">
                            <label for="username" class="block text-sm font-semibold text-slate-900">Email</label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" placeholder="Enter your username or email" class="form-input input-focus w-full pl-12 pr-4 py-3 border-2 border-indigo-200 rounded-xl bg-indigo-50 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 transition-all" required>
                            </div>
                            <?php if (!empty($errors['username'])): ?>
                                <p class="text-rose-500 text-sm font-medium mt-1"><?= htmlspecialchars($errors['username']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label for="password" class="block text-sm font-semibold text-slate-900">Password</label>
                                <a href="<?= BASE_URL ?>/auth/forgot-password.php" class="text-xs font-medium text-indigo-600 link-hover">Forgot password?</a>
                            </div>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input type="password" id="password" name="password" placeholder="Enter your password" class="form-input input-focus w-full pl-12 pr-12 py-3 border-2 border-indigo-200 rounded-xl bg-indigo-50 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 transition-all" required>
                                <button type="button" id="togglePassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600 cursor-pointer">
                                    <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg> 
                                </button>
                            </div>
                            <?php if (!empty($errors['password'])): ?>
                                <p class="text-rose-500 text-sm font-medium mt-1"><?= htmlspecialchars($errors['password']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded border-indigo-300 text-indigo-600 cursor-pointer">
                            <label for="remember" class="ml-2 text-sm text-slate-600 cursor-pointer">Remember me for 30 days</label>
                        </div>

                        <button type="submit" class="btn-primary w-full mt-6 text-black font-semibold py-3 rounded-xl shadow-lg bg-gradient-to-br from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                            <span class="flex items-center justify-center gap-2">
                                Sign In
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </span>
                        </button>
                    </form>

                    <div class="relative my-8">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-indigo-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-3 bg-white text-slate-500 font-medium">New to our store?</span>
                        </div>
                    </div>

                    <a href="<?= BASE_URL ?>/auth/register.php" class="block w-full text-center px-4 py-3 border-2 border-indigo-200 rounded-xl text-slate-900 font-semibold hover:border-indigo-400 hover:bg-indigo-50 transition-all">
                        Create Account
                    </a>
                </div>

                <div class="px-6 sm:px-8 py-6 bg-indigo-50 border-t border-indigo-100">
                    <p class="text-center text-xs text-slate-500">
                        By signing in, you agree to our 
                        <a href="<?= BASE_URL ?>/terms.php" class="text-indigo-600 link-hover font-medium">Terms of Service</a> 
                        and 
                        <a href="<?= BASE_URL ?>/privacy.php" class="text-indigo-600 link-hover font-medium">Privacy Policy</a>
                    </p>
                </div>
            </div>

    

    <script>
        const togglePasswordBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (togglePasswordBtn) {
            togglePasswordBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                
                if (isPassword) {
                    eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-4.803m5.596-3.856a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />';
                } else {
                    eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
                }
            });
        }

        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('ring-2', 'ring-indigo-500', 'ring-opacity-50');
            });
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('ring-2', 'ring-indigo-500', 'ring-opacity-50');
            });
        });
    </script>
</body>
</html>
