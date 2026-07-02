<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . (current_role() === 'admin' ? 'admin/dashboard.php' : 'customer/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request, please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please enter both email and password.';
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'customer/dashboard.php'));
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - SmartShop</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Inter',sans-serif;}
.hero-bg{
    background:
    radial-gradient(circle at top left,#fbcfe8 0%,transparent 50%),
    radial-gradient(circle at top right,#fce7f3 0%,transparent 50%),
    linear-gradient(135deg,#fdf2f8,#fce7f3);
}
</style>
</head>
<body class="hero-bg min-h-screen flex items-center justify-center px-6">

<div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-10">

<a href="../index.php" class="hidden"></a>
<a href="index.php" class="block text-center text-3xl font-extrabold text-pink-600 mb-8">SmartShop</a>

<h1 class="text-2xl font-bold text-gray-800 mb-1">Welcome back</h1>
<p class="text-gray-500 mb-8">Login to your account</p>

<?php if ($error): ?>
<div class="bg-red-50 text-red-600 text-sm px-4 py-3 rounded-xl mb-6"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST" class="space-y-5">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" required
               class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-pink-500"
               placeholder="you@example.com">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <div class="relative">
            <input type="password" name="password" id="password" required
                   class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-pink-500"
                   placeholder="••••••••">
            <button type="button" onclick="togglePassword('password', this)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-pink-600">
                <svg class="eye-on w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <svg class="eye-off w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88" />
                </svg>
            </button>
        </div>
    </div>

    <button type="submit"
            class="w-full bg-pink-600 hover:bg-pink-700 text-white font-semibold py-3 rounded-xl transition">
        Login
    </button>
</form>

<p class="text-center text-gray-500 mt-8">
    Don't have an account?
    <a href="register.php" class="text-pink-600 font-semibold hover:underline">Register</a>
</p>

</div>
<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const eyeOn = btn.querySelector('.eye-on');
    const eyeOff = btn.querySelector('.eye-off');
    if (input.type === 'password') {
        input.type = 'text';
        eyeOn.classList.add('hidden');
        eyeOff.classList.remove('hidden');
    } else {
        input.type = 'password';
        eyeOn.classList.remove('hidden');
        eyeOff.classList.add('hidden');
    }
}
</script>
</body>
</html>