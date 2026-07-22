<?php
require_once __DIR__ . '/../config/connection.php';

$first_name = $last_name = $email = $username = $phone = "";
$password = $confirm_password = "";
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) ? trim($_POST['email']) : '';
    $username = htmlspecialchars(trim($_POST['username']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($first_name)) $errors['first_name'] = 'First name is required';
    if (empty($last_name)) $errors['last_name'] = 'Last name is required';
    if (empty($email)) $errors['email'] = 'A valid email is required';
    if (empty($username)) $errors['username'] = 'Username is required';
    if (empty($phone)) $errors['phone'] = 'Phone number is required';
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    if (empty($errors)) {
        $sql_check = "SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "ss", $email, $username);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $errors['duplicate'] = "Email or username already exists.";
        }
        mysqli_stmt_close($stmt_check);
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $role = 'user';

        $sql = "INSERT INTO users (first_name, last_name, email, username, password, phone, role)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ACACs", $first_name, $last_name, $email, $username, $hashed_password, $phone, $role);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            header('Location: ' . BASE_URL . '/auth/login.php?registered=1');
            exit();
        } else {
            $errors['db'] = "Registration failed. Please try again.";
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
    <title>Create Account - Smart Shopping System</title>
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
        .shape { position: absolute; opacity: 0.15; background: #818cf8; border-radius: 50%; }
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

            <div class="mb-10 text-center sm:text-left">
                <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center gap-2 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow">
                        <span class="text-white font-bold text-lg">AC</span>
                    </div>
                    <span class="text-lg font-bold text-slate-900 group-hover:text-indigo-600 transition">Smart Shopping System</span>
                </a>
            </div>

            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-indigo-200">
                <div class="relative px-6 sm:px-8 pt-8 pb-6 bg-gradient-to-br from-indigo-50 to-indigo-50 border-b border-indigo-100">
                    <div class="space-y-2">
                        <h1 class="text-3xl font-bold text-slate-900">Create Account</h1>
                        <p class="text-slate-600 text-sm">Join us and start shopping premium products</p>
                    </div>
                </div>

                <div class="px-6 sm:px-8 py-8">
                    <?php if (!empty($errors['duplicate'])): ?>
                        <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-xl error-shake">
                            <p class="font-semibold text-rose-900">Registration Error</p>
                            <p class="text-rose-800 text-sm mt-1"><?= htmlspecialchars($errors['duplicate']) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($errors['db'])): ?>
                        <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-xl error-shake">
                            <p class="font-semibold text-rose-900">Something went wrong</p>
                            <p class="text-rose-800 text-sm mt-1"><?= htmlspecialchars($errors['db']) ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="space-y-5" novalidate>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="first_name" class="block text-sm font-semibold text-slate-900">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name) ?>" placeholder="Sabina" class="form-input input-focus w-full px-4 py-3 border-2 border-indigo-200 rounded-xl bg-indigo-50 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 transition-all" required>
                                <?php if (!empty($errors['first_name'])): ?>
                                    <p class="text-rose-500 text-sm font-medium mt-1"><?= htmlspecialchars($errors['first_name']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="space-y-2">
                                <label for="last_name" class="block text-sm font-semibold text-slate-900">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name) ?>" placeholder="Kandel" class="form-input input-focus w-full px-4 py-3 border-2 border-indigo-200 rounded-xl bg-indigo-50 text-gray-200 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 transition-all" required>
                                <?php if (!empty($errors['last_name'])): ?>
                                    <p class="text-rose-500 text-sm font-medium mt-1"><?= htmlspecialchars($errors['last_name']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="email" class="block text-sm font-semibold text-slate-900">Email Address</label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </div>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="kandelsabina@example.com" class="form-input input-focus w-full pl-12 pr-4 py-3 border-2 border-indigo-200 rounded-xl bg-indigo-50 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 transition-all" required>
                            </div>
                            <?php if (!empty($errors['email'])): ?>
                                <p class="text-rose-500 text-sm font-medium mt-1"><?= htmlspecialchars($errors['email']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-2">
                            <label for="username" class="block text-sm font-semibold text-slate-900">Username</label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" placeholder="kandelsabina" class="form-input input-focus w-full pl-12 pr-4 py-3 border-2 border-indigo-200 rounded-xl bg-indigo-50 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 transition-all" required>
                            </div>
                            <?php if (!empty($errors['username'])): ?>
                                <p class="text-rose-500 text-sm font-medium mt-1"><?= htmlspecialchars($errors['username']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-2">
                            <label for="phone" class="block text-sm font-semibold text-slate-900">Phone Number</label>
                            <div class="relative">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="+977 98XXXXXXXX" class="form-input input-focus w-full pl-12 pr-4 py-3 border-2 border-indigo-200 rounded-xl bg-indigo-50 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 transition-all" required>
                            </div>
                            <?php if (!empty($errors['phone'])): ?>
                                <p class="text-rose-500 text-sm font-medium mt-1"><?= htmlspecialchars($errors['phone']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="password" class="block text-sm font-semibold text-slate-900">Password</label>
                                <input type="password" id="password" name="password" placeholder="••••••••" class="form-input input-focus w-full px-4 py-3 border-2 border-indigo-200 rounded-xl bg-indigo-50 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 transition-all" required>
                                <?php if (!empty($errors['password'])): ?>
                                    <p class="text-rose-500 text-sm font-medium mt-1"><?= htmlspecialchars($errors['password']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="space-y-2">
                                <label for="confirm_password" class="block text-sm font-semibold text-slate-900">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" class="form-input input-focus w-full px-4 py-3 border-2 border-indigo-200 rounded-xl bg-indigo-50 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 transition-all" required>
                                <?php if (!empty($errors['confirm_password'])): ?>
                                    <p class="text-rose-500 text-sm font-medium mt-1"><?= htmlspecialchars($errors['confirm_password']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary w-full mt-6 text-black font-semibold py-3 rounded-xl shadow-lg bg-gradient-to-br from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                            <span class="flex items-center justify-center gap-2">
                                Create Account
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
                            <span class="px-3 bg-white text-slate-500 font-medium">Already have an account?</span>
                        </div>
                    </div>

                    <a href="<?= BASE_URL ?>/auth/login.php" class="block w-full text-center px-4 py-3 border-2 border-indigo-200 rounded-xl text-slate-900 font-semibold hover:border-indigo-400 hover:bg-indigo-50 transition-all">
                        Sign In
                    </a>
                </div>

                <div class="px-6 sm:px-8 py-6 bg-indigo-50 border-t border-indigo-100">
                    <p class="text-center text-xs text-slate-500">
                        By creating an account, you agree to our 
                        <a href="<?= BASE_URL ?>/terms.php" class="text-indigo-600 link-hover font-medium">Terms of Service</a> 
                        and 
                        <a href="<?= BASE_URL ?>/privacy.php" class="text-indigo-600 link-hover font-medium">Privacy Policy</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
