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
        mysqli_stmt_bind_param($stmt, "sssssss", $first_name, $last_name, $email, $username, $hashed_password, $phone, $role);

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
    <title>Sign Up - Smart Shopping System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/enterprise-theme.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            background: linear-gradient(160deg, #fff7fa 0%, #ffffff 40%, #fde8f0 100%) !important;
        }
        .register-card {
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.04),
                        0 20px 50px -12px rgb(240 101 157 / 0.15);
        }
        .register-input {
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .register-input:focus {
            outline: none;
            border-color: #f0659d;
            box-shadow: 0 0 0 3px rgb(240 101 157 / 0.15);
        }
        .register-input.is-error {
            border-color: #e11d48;
            background-color: #fff1f2;
        }
        .error-shake { animation: shake 0.4s ease-in-out; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body class="enterprise-theme min-h-screen text-slate-800">

    <div class="min-h-screen flex flex-col">
        <!-- Top bar -->
        <header class="px-6 py-5">
            <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center gap-2.5 text-slate-700 hover:text-pink-600 transition-colors">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-pink-500 text-sm font-bold text-white shadow-sm">AC</div>
                <span class="text-sm font-semibold tracking-tight">Smart Shopping System</span>
            </a>
        </header>

        <!-- Main -->
        <main class="flex-1 flex items-start justify-center px-4 pb-12 sm:px-6">
            <div class="register-card w-full max-w-[520px] rounded-2xl border border-pink-100 bg-white overflow-hidden">

                <!-- Card header -->
                <div class="border-b border-pink-50 bg-gradient-to-r from-pink-50/80 to-white px-6 py-7 sm:px-8">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-pink-600">Create account</p>
                    <h1 class="mt-1.5 text-2xl font-bold tracking-tight text-slate-900 sm:text-[1.75rem]">Sign up to start shopping</h1>
                    <p class="mt-1.5 text-sm text-slate-500">Enter your details below. Registration takes less than a minute.</p>
                </div>

                <div class="px-6 py-7 sm:px-8">
                    <?php if (!empty($errors['duplicate'])): ?>
                        <div class="mb-5 flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 error-shake" role="alert">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <p class="text-sm text-rose-700"><?= htmlspecialchars($errors['duplicate']) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($errors['db'])): ?>
                        <div class="mb-5 flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 error-shake" role="alert">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <p class="text-sm text-rose-700"><?= htmlspecialchars($errors['db']) ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="space-y-5" novalidate>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="first_name" class="mb-1.5 block text-sm font-medium text-slate-700">First name</label>
                                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name) ?>" placeholder="Sabina"
                                    class="register-input w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400<?= !empty($errors['first_name']) ? ' is-error' : '' ?>"
                                    required autocomplete="given-name">
                                <?php if (!empty($errors['first_name'])): ?>
                                    <p class="mt-1 text-xs text-rose-600"><?= htmlspecialchars($errors['first_name']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label for="last_name" class="mb-1.5 block text-sm font-medium text-slate-700">Last name</label>
                                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name) ?>" placeholder="Kandel"
                                    class="register-input w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400<?= !empty($errors['last_name']) ? ' is-error' : '' ?>"
                                    required autocomplete="family-name">
                                <?php if (!empty($errors['last_name'])): ?>
                                    <p class="mt-1 text-xs text-rose-600"><?= htmlspecialchars($errors['last_name']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="you@example.com"
                                class="register-input w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400<?= !empty($errors['email']) ? ' is-error' : '' ?>"
                                required autocomplete="email">
                            <?php if (!empty($errors['email'])): ?>
                                <p class="mt-1 text-xs text-rose-600"><?= htmlspecialchars($errors['email']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="username" class="mb-1.5 block text-sm font-medium text-slate-700">Username</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" placeholder="Choose a username"
                                class="register-input w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400<?= !empty($errors['username']) ? ' is-error' : '' ?>"
                                required autocomplete="username">
                            <?php if (!empty($errors['username'])): ?>
                                <p class="mt-1 text-xs text-rose-600"><?= htmlspecialchars($errors['username']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="phone" class="mb-1.5 block text-sm font-medium text-slate-700">Phone number</label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="+977 98XXXXXXXX"
                                class="register-input w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400<?= !empty($errors['phone']) ? ' is-error' : '' ?>"
                                required autocomplete="tel">
                            <?php if (!empty($errors['phone'])): ?>
                                <p class="mt-1 text-xs text-rose-600"><?= htmlspecialchars($errors['phone']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                                <div class="relative">
                                    <input type="password" id="password" name="password" placeholder="Min. 8 chars"
                                        class="register-input w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 pr-9 text-sm text-slate-900 placeholder:text-slate-400<?= !empty($errors['password']) ? ' is-error' : '' ?>"
                                        required autocomplete="new-password">
                                    <button type="button" class="toggle-pw absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-slate-400 hover:text-slate-600" data-target="password" aria-label="Show password" tabindex="-1">
                                        <svg class="h-4 w-4 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg class="h-4 w-4 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    </button>
                                </div>
                                <?php if (!empty($errors['password'])): ?>
                                    <p class="mt-1 text-xs text-rose-600"><?= htmlspecialchars($errors['password']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label for="confirm_password" class="mb-1.5 block text-sm font-medium text-slate-700">Confirm</label>
                                <div class="relative">
                                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password"
                                        class="register-input w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 pr-9 text-sm text-slate-900 placeholder:text-slate-400<?= !empty($errors['confirm_password']) ? ' is-error' : '' ?>"
                                        required autocomplete="new-password">
                                    <button type="button" class="toggle-pw absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 text-slate-400 hover:text-slate-600" data-target="confirm_password" aria-label="Show password" tabindex="-1">
                                        <svg class="h-4 w-4 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg class="h-4 w-4 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    </button>
                                </div>
                                <?php if (!empty($errors['confirm_password'])): ?>
                                    <p class="mt-1 text-xs text-rose-600"><?= htmlspecialchars($errors['confirm_password']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="!mt-6 w-full rounded-lg bg-pink-500 py-3 text-sm font-semibold text-white transition hover:bg-pink-600 focus:outline-none focus:ring-4 focus:ring-pink-200">
                            Create account
                        </button>
                    </form>
                </div>

                <!-- Card footer -->
                <div class="border-t border-pink-50 bg-slate-50/50 px-6 py-5 sm:px-8">
                    <p class="text-center text-sm text-slate-600">
                        Already have an account?
                        <a href="<?= BASE_URL ?>/auth/login.php" class="font-semibold text-pink-600 hover:text-pink-700 hover:underline">Sign in</a>
                    </p>
                    <p class="mt-3 text-center text-xs text-slate-400">
                        By signing up you agree to our
                        <a href="<?= BASE_URL ?>/terms.php" class="text-slate-500 hover:text-pink-600 underline-offset-2 hover:underline">Terms</a>
                        and
                        <a href="<?= BASE_URL ?>/privacy.php" class="text-slate-500 hover:text-pink-600 underline-offset-2 hover:underline">Privacy Policy</a>.
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.querySelectorAll('.toggle-pw').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var input = document.getElementById(btn.dataset.target);
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.querySelector('.eye-open').classList.toggle('hidden', show);
                btn.querySelector('.eye-closed').classList.toggle('hidden', !show);
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
    </script>
</body>
</html>
