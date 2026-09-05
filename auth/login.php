<?php
require_once __DIR__ . '/../config/connection.php';

$username = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        $errors['login'] = 'Enter your username or email.';
    } elseif ($password === '') {
        $errors['login'] = 'Enter your password.';
    } else {
        $sql = "SELECT id, username, first_name, last_name, email, password, role FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            header('Location: ' . BASE_URL . ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/index.php'));
            exit();
        }
        $errors['login'] = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-pink-50 text-slate-800 flex items-center justify-center px-4 py-10">
    <form method="POST" class="w-full max-w-md space-y-5 rounded-2xl border border-indigo-100 bg-white p-6 shadow-xl shadow-indigo-100/60 sm:p-8">
        <a href="<?= BASE_URL ?>/index.php" class="block text-sm text-slate-500 hover:text-pink-600">← Store</a>
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-pink-600">Welcome back</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Login</h1>
            <p class="mt-1 text-sm text-slate-500">Enter your details to continue shopping.</p>
        </div>

        <?php if (isset($_GET['registered'])): ?>
            <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">Account created. Please log in.</p>
        <?php endif; ?>
        <?php if (!empty($errors['login'])): ?>
            <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-600"><?= htmlspecialchars($errors['login']) ?></p>
        <?php endif; ?>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700" for="username">Username or email</label>
            <input id="username" name="username" value="<?= htmlspecialchars($username) ?>" required
                class="w-full rounded-xl border-2 border-indigo-100 bg-indigo-50/50 px-4 py-3 text-sm transition placeholder:text-slate-400 focus:border-pink-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-pink-100">
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700" for="password">Password</label>
            <input id="password" name="password" type="password" required
                class="w-full rounded-xl border-2 border-indigo-100 bg-indigo-50/50 px-4 py-3 text-sm transition placeholder:text-slate-400 focus:border-pink-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-pink-100">
        </div>
        <button type="submit" class="w-full rounded-xl bg-pink-600 py-3 text-sm font-semibold text-white shadow-md shadow-pink-200 transition hover:bg-pink-700 hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-pink-200">Login</button>
        <a href="<?= BASE_URL ?>/auth/register.php" class="block w-full rounded-xl border-2 border-indigo-100 px-4 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-pink-300 hover:bg-pink-50 hover:text-pink-700">
            Create an account
        </a>
    </form>
</body>
</html>
