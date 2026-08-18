<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

auth_start();

if (auth_user_id() !== null) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } else {
        $db = db();
        $check = $db->prepare('SELECT id FROM users WHERE email = :email');
        $check->bindValue(':email', $email, SQLITE3_TEXT);
        if ($check->execute()->fetchArray()) {
            $error = 'Email already registered';
        } else {
            auth_register($email, $password);
            $success = 'Account created. You can now log in.';
        }
    }
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — MiniRank</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width:400px; margin-top:3rem;">
    <h1 style="text-align:center; margin-bottom:1.5rem;">MiniRank</h1>

    <div class="modal" style="box-shadow:none; border:1px solid var(--border);">
        <h2 style="text-align:center;">Create Account</h2>

        <?php if ($error): ?>
        <div style="background:#fdecea; color:var(--danger); padding:0.6rem 0.85rem; border-radius:var(--radius); margin-bottom:1rem; font-size:0.9rem;">
            <?php echo esc($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div style="background:#e8f5e9; color:var(--green); padding:0.6rem 0.85rem; border-radius:var(--radius); margin-bottom:1rem; font-size:0.9rem;">
            <?php echo esc($success); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="8" required>
            </div>
            <div class="form-group">
                <label for="confirm">Confirm password</label>
                <input type="password" id="confirm" name="confirm" minlength="8" required>
            </div>
            <button class="btn" type="submit" style="width:100%; margin-top:0.5rem;">Register</button>
        </form>

        <p style="text-align:center; margin-top:1rem; font-size:0.9rem; color:var(--muted);">
            Already have an account? <a href="login.php" style="color:var(--primary);">Log in</a>
        </p>
    </div>
</div>
</body>
</html>
