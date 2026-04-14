<?php
function getBoardLayout($level)
{
    $layouts = [
        'beginner' => ['s' => [17 => 7, 62 => 19, 98 => 79], 'l' => [4 => 14, 9 => 31, 20 => 38]],
        'standard' => ['s' => [17 => 7, 44 => 22, 62 => 19, 88 => 24, 95 => 56, 98 => 79], 'l' => [4 => 14, 9 => 31, 20 => 38, 51 => 67, 71 => 91]],
        'expert' => ['s' => [17 => 7, 32 => 10, 48 => 2, 62 => 19, 81 => 3, 88 => 24, 91 => 5, 95 => 56, 99 => 1], 'l' => [4 => 14, 9 => 31, 20 => 38, 51 => 67]]
    ];
    return $layouts[$level] ?? $layouts['beginner'];
}

function initializeGame($force = false)
{
    if ($force || !isset($_SESSION['positions'])) {
        $_SESSION['positions'] = [1, 1];
        $_SESSION['current_turn'] = 0;
        $_SESSION['turn_count'] = 1;
        $_SESSION['log'] = ["Game started!"];
        $_SESSION['last_event'] = "Welcome!";
        $_SESSION['last_roll'] = null;
    }
}

function handleRoll($playerIndex)
{
    $layout = getBoardLayout($_SESSION['difficulty'] ?? 'beginner');
    $roll = rand(1, 6);
    $_SESSION['last_roll'] = $roll;
    $newPos = $_SESSION['positions'][$playerIndex] + $roll;
    if ($newPos >= 100)
        return 100;
    if (isset($layout['l'][$newPos]))
        $newPos = $layout['l'][$newPos];
    elseif (isset($layout['s'][$newPos]))
        $newPos = $layout['s'][$newPos];
    $_SESSION['last_event'] = "Player " . ($playerIndex + 1) . " rolled a $roll.";
    return $newPos;
}
?>