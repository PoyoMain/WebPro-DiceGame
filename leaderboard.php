<?php session_start(); ?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="style.css">
</head>

<body class="container">
    <h1>🏆 Player <?php echo htmlspecialchars($_GET['win'] ?? ''); ?> Wins!</h1>
    <div class="log-box">
        <h3>Adventure Recap:</h3>
        <ul><?php if (isset($_SESSION['log'])) {
            foreach ($_SESSION['log'] as $l)
                echo "<li>$l</li>";
        } ?></ul>
    </div>
    <a href="game.php?reset=true">Play Again</a> | <a href="logout.php">Logout</a>
</body>

</html>