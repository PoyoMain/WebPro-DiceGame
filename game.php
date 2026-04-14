<?php
require_once 'auth.php';
require_once 'game_logic.php';
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}
if (isset($_GET['reset']))
    initializeGame(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['roll'])) {
    $turn = $_SESSION['current_turn'];
    $_SESSION['positions'][$turn] = handleRoll($turn);
    if ($_SESSION['positions'][$turn] >= 100) {
        header("Location: leaderboard.php?win=" . ($turn + 1));
        exit();
    }
    $_SESSION['current_turn'] = ($_SESSION['current_turn'] == 0) ? 1 : 0;
}
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <h2>User: <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        <div class="dice-box">
            <div class="dice-face"><?php echo $_SESSION['last_roll'] ?? '?'; ?></div>
            <p>Turn: <strong>Player <?php echo ($_SESSION['current_turn'] + 1); ?></strong></p>
        </div>
        <div class="board">
            <?php
            $layout = getBoardLayout($_SESSION['difficulty']);
            for ($i = 100; $i >= 1; $i--) {
                $p1 = ($_SESSION['positions'][0] == $i) ? "👤" : "";
                $p2 = ($_SESSION['positions'][1] == $i) ? "👤" : "";
                echo "<div class='cell'>$i $p1 $p2</div>";
            }
            ?>
        </div>
        <form method="POST"><button type="submit" name="roll" class="roll-btn">ROLL DICE</button></form>
        <a href="logout.php">Logout</a>
    </div>
</body>

</html>