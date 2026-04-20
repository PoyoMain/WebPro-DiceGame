<?php
require_once 'auth.php';
require_once 'game_logic.php';

$user_file = 'data/users.txt';
if (!file_exists('data')) { mkdir('data', 0777, true); }
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username']);
    $p = $_POST['password'];

    if (isset($_POST['register']) && !empty($u) && !empty($p)) {
        $hash = password_hash($p, PASSWORD_DEFAULT);
        file_put_contents($user_file, $u . "|" . $hash . PHP_EOL, FILE_APPEND);
        $_SESSION['username'] = $u;
        $_SESSION['difficulty'] = $_POST['diff'] ?? 'beginner';
        initializeGame(true);
        header("Location: game.php");
        exit();
    } 
    elseif (isset($_POST['login'])) {
        if (file_exists($user_file)) {
            $lines = file($user_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $parts = explode('|', trim($line));
                if (count($parts) === 2) {
                    if ($u === $parts[0] && password_verify($p, $parts[1])) {
                        $_SESSION['username'] = $parts[0];
                        $_SESSION['difficulty'] = $_POST['diff'] ?? 'beginner';
                        initializeGame(true);
                        header("Location: game.php");
                        exit();
                    }
                }
            }
        }
        $error_msg = "Invalid username or password.";
    }
}

if (isLoggedIn() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: game.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="style.css"><title>Login</title></head>
<body>
    <div class="auth-box">
        <h1>🎲 Dice Adventure</h1>
        <?php if ($error_msg): ?><p style="color: #e94560;"><?php echo $error_msg; ?></p><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="diff">
                <option value="beginner">Beginner</option>
                <option value="standard">Standard</option>
                <option value="expert">Expert</option>
            </select>
            <button type="submit" name="login">Login</button>
            <button type="submit" name="register" style="background:#533483">Register</button>
        </form>
    </div>
</body>
</html>
