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
        $_SESSION['last_move'] = null;
    }
}

function handleRoll($playerIndex)
{
    $layout = getBoardLayout($_SESSION['difficulty'] ?? 'beginner');
    $roll = rand(1, 6);
    $start = $_SESSION['positions'][$playerIndex];
    $steppedTo = min($start + $roll, 100);
    $finalPosition = $steppedTo;
    $effect = 'normal';

    if ($steppedTo < 100) {
        if (isset($layout['l'][$steppedTo])) {
            $finalPosition = $layout['l'][$steppedTo];
            $effect = 'ladder';
        } elseif (isset($layout['s'][$steppedTo])) {
            $finalPosition = $layout['s'][$steppedTo];
            $effect = 'snake';
        }
    }

    $won = ($finalPosition >= 100);
    $_SESSION['last_roll'] = $roll;

    $eventMessage = "Player " . ($playerIndex + 1) . " rolled a $roll and moved from $start to $steppedTo.";
    if ($effect === 'ladder') {
        $eventMessage .= " Ladder up to $finalPosition.";
    } elseif ($effect === 'snake') {
        $eventMessage .= " Snake down to $finalPosition.";
    } elseif ($won) {
        $eventMessage = "Player " . ($playerIndex + 1) . " rolled a $roll and reached 100.";
    }

    $_SESSION['last_event'] = $eventMessage;
    $_SESSION['log'][] = $eventMessage;
    $_SESSION['last_move'] = [
        'player' => $playerIndex,
        'roll' => $roll,
        'from' => $start,
        'stepped_to' => $steppedTo,
        'to' => $finalPosition,
        'effect' => $effect,
        'won' => $won,
    ];

    return $_SESSION['last_move'];
}
?>