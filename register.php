<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . (current_role() === 'admin' ? 'admin/dashboard.php' : 'customer/dashboard.php'));
    exit;
}

$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request, please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $old['name'] = $name;
        $old['email'] = $email;

        // ---- Name ----
        if ($name === '') {
            $errors[] = 'Full name is required.';
        } elseif (mb_strlen($name) < 2) {
            $errors[] = 'Full name must be at least 2 characters.';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Full name must not exceed 100 characters.';
        } elseif (!preg_match("/^[\p{L}\s.'-]+$/u", $name)) {
            $errors[] = 'Full name may only contain letters, spaces, apostrophes, periods and hyphens.';
        }

        // ---- Email ----
        if ($email === '') {
            $errors[] = 'Email is required.';
        } elseif (mb_strlen($email) > 150) {
            $errors[] = 'Email must not exceed 150 characters.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        // ---- Password ----
        if ($password === '') {
            $errors[] = 'Password is required.';
        } else {
            if (mb_strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            }
            if (mb_strlen($password) > 72) {
                $errors[] = 'Password must not exceed 72 characters.';
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Password must contain at least one uppercase letter.';
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = 'Password must contain at least one lowercase letter.';
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = 'Password must contain at least one number.';
            }
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = 'Password must contain at least one special character.';
            }
            if (preg_match('/\s/', $password)) {
                $errors[] = 'Password must not contain spaces.';
            }
        }

        // ---- Confirm password ----
        if ($confirm === '') {
            $errors[] = 'Please confirm your password.';
        } elseif ($password !== '' && $password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        // ---- Duplicate email check (only if no errors so far) ----
        if (empty($errors)) {
            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($check, 's', $email);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                $errors[] = 'An account with this email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'customer'; // customers only — admin accounts are seeded directly in the DB

                $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $hash, $role);

                if (mysqli_stmt_execute($stmt)) {
                    $newId = mysqli_insert_id($conn);
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $newId;
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = $role;

                    header('Location: customer/dashboard.php');
                    exit;
                } else {
                    $errors[] = 'Something went wrong. Please try again.';
                }
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
<title>Register - SmartShop</title>
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
.field-error{display:none;}
.field-error.show{display:block;}
input.invalid{border-color:#ef4444 !important;}
input.valid{border-color:#22c55e !important;}
.strength-bar{height:4px;border-radius:9999px;transition:all .25s ease;}
</style>
</head>
<body class="hero-bg min-h-screen flex items-center justify-center px-6 py-16">

<div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-10">

<a href="index.php" class="block text-center text-3xl font-extrabold text-pink-600 mb-8">SmartShop</a>

<h1 class="text-2xl font-bold text-gray-800 mb-1">Create your account</h1>
<p class="text-gray-500 mb-8">Join SmartShop as a customer</p>

<?php if (!empty($errors)): ?>
<div class="bg-red-50 text-red-600 text-sm px-4 py-3 rounded-xl mb-6 space-y-1">
    <?php foreach ($errors as $e): ?>
        <p>• <?= h($e) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" id="registerForm" class="space-y-5" novalidate>
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
        <input type="text" name="name" id="name" required value="<?= h($old['name']) ?>"
               class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-pink-500"
               placeholder="Jane Doe" autocomplete="name">
        <p class="field-error text-xs text-red-500 mt-1" id="name-error"></p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" id="email" required value="<?= h($old['email']) ?>"
               class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-pink-500"
               placeholder="you@example.com" autocomplete="email">
        <p class="field-error text-xs text-red-500 mt-1" id="email-error"></p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <div class="relative">
            <input type="password" name="password" id="password" required minlength="8" maxlength="72"
                   class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-pink-500"
                   placeholder="At least 8 characters" autocomplete="new-password">
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
        <div class="flex gap-1 mt-2" id="strength-bars">
            <div class="strength-bar bg-gray-200 flex-1" data-bar="1"></div>
            <div class="strength-bar bg-gray-200 flex-1" data-bar="2"></div>
            <div class="strength-bar bg-gray-200 flex-1" data-bar="3"></div>
            <div class="strength-bar bg-gray-200 flex-1" data-bar="4"></div>
        </div>
        <p class="text-xs text-gray-400 mt-1" id="strength-label">Use 8+ characters with upper, lower, number &amp; symbol</p>
        <p class="field-error text-xs text-red-500 mt-1" id="password-error"></p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
        <div class="relative">
            <input type="password" name="confirm_password" id="confirm_password" required minlength="8" maxlength="72"
                   class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-pink-500"
                   placeholder="Repeat password" autocomplete="new-password">
            <button type="button" onclick="togglePassword('confirm_password', this)"
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
        <p class="field-error text-xs text-red-500 mt-1" id="confirm_password-error"></p>
    </div>

    <button type="submit"
            class="w-full bg-pink-600 hover:bg-pink-700 text-white font-semibold py-3 rounded-xl transition">
        Create Account
    </button>
</form>

<p class="text-center text-gray-500 mt-8">
    Already have an account?
    <a href="login.php" class="text-pink-600 font-semibold hover:underline">Login</a>
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

const nameInput = document.getElementById('name');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const confirmInput = document.getElementById('confirm_password');
const form = document.getElementById('registerForm');

const NAME_REGEX = /^[\p{L}\s.'-]+$/u;
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function setError(input, msg) {
    const el = document.getElementById(input.id + '-error');
    if (msg) {
        el.textContent = msg;
        el.classList.add('show');
        input.classList.add('invalid');
        input.classList.remove('valid');
    } else {
        el.textContent = '';
        el.classList.remove('show');
        input.classList.remove('invalid');
        input.classList.add('valid');
    }
}

function validateName() {
    const v = nameInput.value.trim();
    if (v === '') { setError(nameInput, 'Full name is required.'); return false; }
    if (v.length < 2) { setError(nameInput, 'Full name must be at least 2 characters.'); return false; }
    if (v.length > 100) { setError(nameInput, 'Full name must not exceed 100 characters.'); return false; }
    if (!NAME_REGEX.test(v)) { setError(nameInput, 'Only letters, spaces, apostrophes, periods and hyphens allowed.'); return false; }
    setError(nameInput, '');
    return true;
}

function validateEmail() {
    const v = emailInput.value.trim();
    if (v === '') { setError(emailInput, 'Email is required.'); return false; }
    if (v.length > 150) { setError(emailInput, 'Email must not exceed 150 characters.'); return false; }
    if (!EMAIL_REGEX.test(v)) { setError(emailInput, 'Please enter a valid email address.'); return false; }
    setError(emailInput, '');
    return true;
}

function passwordRules(v) {
    return {
        length: v.length >= 8 && v.length <= 72,
        upper: /[A-Z]/.test(v),
        lower: /[a-z]/.test(v),
        number: /[0-9]/.test(v),
        special: /[^A-Za-z0-9]/.test(v),
        noSpace: !/\s/.test(v),
    };
}

function updateStrengthMeter(v) {
    const r = passwordRules(v);
    const score = [r.length, r.upper && r.lower, r.number, r.special].filter(Boolean).length;
    const bars = document.querySelectorAll('#strength-bars .strength-bar');
    const colors = ['bg-red-400', 'bg-orange-400', 'bg-yellow-400', 'bg-green-500'];
    const labels = ['Weak', 'Fair', 'Good', 'Strong'];

    bars.forEach((bar, i) => {
        bar.className = 'strength-bar flex-1 ' + (i < score && v.length > 0 ? colors[score - 1] : 'bg-gray-200');
    });

    const label = document.getElementById('strength-label');
    if (v.length === 0) {
        label.textContent = 'Use 8+ characters with upper, lower, number & symbol';
        label.className = 'text-xs text-gray-400 mt-1';
    } else {
        label.textContent = labels[Math.max(score - 1, 0)] + ' password';
        label.className = 'text-xs mt-1 ' + ['text-red-500','text-orange-500','text-yellow-600','text-green-600'][Math.max(score - 1, 0)];
    }
}

function validatePassword() {
    const v = passwordInput.value;
    const r = passwordRules(v);
    updateStrengthMeter(v);

    if (v === '') { setError(passwordInput, 'Password is required.'); return false; }
    if (!r.length) { setError(passwordInput, 'Password must be 8–72 characters.'); return false; }
    if (!r.upper) { setError(passwordInput, 'Add at least one uppercase letter.'); return false; }
    if (!r.lower) { setError(passwordInput, 'Add at least one lowercase letter.'); return false; }
    if (!r.number) { setError(passwordInput, 'Add at least one number.'); return false; }
    if (!r.special) { setError(passwordInput, 'Add at least one special character.'); return false; }
    if (!r.noSpace) { setError(passwordInput, 'Password must not contain spaces.'); return false; }
    setError(passwordInput, '');
    return true;
}

function validateConfirm() {
    const v = confirmInput.value;
    if (v === '') { setError(confirmInput, 'Please confirm your password.'); return false; }
    if (v !== passwordInput.value) { setError(confirmInput, 'Passwords do not match.'); return false; }
    setError(confirmInput, '');
    return true;
}

nameInput.addEventListener('input', validateName);
emailInput.addEventListener('input', validateEmail);
passwordInput.addEventListener('input', () => { validatePassword(); if (confirmInput.value) validateConfirm(); });
confirmInput.addEventListener('input', validateConfirm);

form.addEventListener('submit', function (e) {
    const validName = validateName();
    const validEmail = validateEmail();
    const validPassword = validatePassword();
    const validConfirm = validateConfirm();

    if (!(validName && validEmail && validPassword && validConfirm)) {
        e.preventDefault();
    }
});
</script>

</body>
</html>