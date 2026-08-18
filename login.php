<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

auth_start();

if (auth_user_id() !== null) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required';
    } else {
        $userId = auth_login($email, $password);
        if ($userId !== null) {
            header('Location: index.php');
            exit;
        }
        $error = 'Invalid email or password';
    }
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MiniRank</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width:400px; margin-top:3rem;">
    <h1 style="text-align:center; margin-bottom:1.5rem;">MiniRank</h1>

    <div class="modal" style="box-shadow:none; border:1px solid var(--border);">
        <h2 style="text-align:center;">Log In</h2>

        <?php if ($error): ?>
        <div style="background:#fdecea; color:var(--danger); padding:0.6rem 0.85rem; border-radius:var(--radius); margin-bottom:1rem; font-size:0.9rem;">
            <?php echo esc($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button class="btn" type="submit" style="width:100%; margin-top:0.5rem;">Log In</button>
        </form>

        <p style="text-align:center; margin-top:1rem; font-size:0.9rem; color:var(--muted);">
            No account? <a href="register.php" style="color:var(--primary);">Register</a>
        </p>
    </div>
</div>
</body>
</html>
