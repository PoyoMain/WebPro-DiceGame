<?php
require_once 'auth.php';
require_once 'game_logic.php';

if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['reset'])) {
    initializeGame(true);
} else {
    initializeGame(false);
}

function isAjaxRollRequest()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['roll'])) {
    $turn = $_SESSION['current_turn'];
    $move = handleRoll($turn);
    $_SESSION['positions'][$turn] = $move['to'];

    $winner = null;
    if ($move['won']) {
        $winner = $turn + 1;
    } else {
        $_SESSION['current_turn'] = ($_SESSION['current_turn'] == 0) ? 1 : 0;
        $_SESSION['turn_count'] = ($_SESSION['turn_count'] ?? 1) + 1;
    }

    if (isAjaxRollRequest()) {
        header('Content-Type: application/json');
        echo json_encode([
            'current_turn' => $_SESSION['current_turn'],
            'positions' => array_values($_SESSION['positions']),
            'last_roll' => $_SESSION['last_roll'],
            'last_event' => $_SESSION['last_event'],
            'move' => $_SESSION['last_move'],
            'winner' => $winner,
        ]);
        exit();
    }

    if ($winner !== null) {
        header("Location: leaderboard.php?win=" . ($turn + 1));
        exit();
    }
}

$layout = getBoardLayout($_SESSION['difficulty'] ?? 'beginner');
$positions = array_values($_SESSION['positions']);
$lastRoll = $_SESSION['last_roll'] ?? '?';
$lastEvent = $_SESSION['last_event'] ?? 'Welcome!';
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="style.css">
    <title>Dice Adventure</title>
</head>

<body>
    <div class="container">
        <div class="status-bar">
            <h2>User: <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
            <div class="status-actions">
                <a href="game.php?reset=true">Reset</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
        <div class="dice-box">
            <div class="dice-face" id="dice-face"><?php echo htmlspecialchars((string) $lastRoll); ?></div>
            <p>Turn: <strong id="turn-indicator">Player <?php echo ($_SESSION['current_turn'] + 1); ?></strong></p>
            <p class="event-text" id="event-text"><?php echo htmlspecialchars($lastEvent); ?></p>
        </div>
        <div class="board" id="board">
            <?php
            for ($i = 100; $i >= 1; $i--) {
                $cellClasses = ['cell'];
                if (isset($layout['l'][$i])) {
                    $cellClasses[] = 'ladder-cell';
                }
                if (isset($layout['s'][$i])) {
                    $cellClasses[] = 'snake-cell';
                }
                echo "<div class='" . implode(' ', $cellClasses) . "' data-cell='$i'>";
                echo "<span class='cell-num'>$i</span>";
                echo "<div class='token-slot'></div>";
                echo "</div>";
            }
            ?>
        </div>
        <form method="POST" id="roll-form">
            <button type="submit" name="roll" class="roll-btn" id="roll-btn">ROLL DICE</button>
        </form>
        <div class="legend">
            <span><strong>P1</strong> Player 1</span>
            <span><strong>P2</strong> Player 2</span>
            <span class="ladder-pill">Ladder</span>
            <span class="snake-pill">Snake</span>
        </div>
    </div>
    <script>
        const board = document.getElementById('board');
        const rollForm = document.getElementById('roll-form');
        const rollButton = document.getElementById('roll-btn');
        const diceFace = document.getElementById('dice-face');
        const turnIndicator = document.getElementById('turn-indicator');
        const eventText = document.getElementById('event-text');
        const tokenElements = [];
        const appState = {
            positions: <?php echo json_encode($positions); ?>,
            currentTurn: <?php echo json_encode((int) $_SESSION['current_turn']); ?>,
        };
        let audioContext;

        function wait(duration) {
            return new Promise((resolve) => window.setTimeout(resolve, duration));
        }

        async function ensureAudioContext() {
            if (!audioContext) {
                const Context = window.AudioContext || window.webkitAudioContext;
                if (!Context) {
                    return null;
                }
                audioContext = new Context();
            }

            if (audioContext.state === 'suspended') {
                await audioContext.resume();
            }

            return audioContext;
        }

        function playTone(frequency, duration, type, gain, delaySeconds) {
            if (!audioContext) {
                return;
            }

            const oscillator = audioContext.createOscillator();
            const volume = audioContext.createGain();
            const startAt = audioContext.currentTime + (delaySeconds || 0);
            const stopAt = startAt + duration;

            oscillator.type = type || 'sine';
            oscillator.frequency.setValueAtTime(frequency, startAt);

            volume.gain.setValueAtTime(0.0001, startAt);
            volume.gain.exponentialRampToValueAtTime(gain || 0.04, startAt + 0.01);
            volume.gain.exponentialRampToValueAtTime(0.0001, stopAt);

            oscillator.connect(volume);
            volume.connect(audioContext.destination);

            oscillator.start(startAt);
            oscillator.stop(stopAt);
        }

        function playDiceSound() {
            const bursts = [220, 260, 300, 340, 380];
            bursts.forEach((frequency, index) => {
                playTone(frequency, 0.06, 'square', 0.035, index * 0.05);
            });
        }

        function playStepSound(stepNumber) {
            playTone(180 + (stepNumber * 18), 0.05, 'triangle', 0.02, 0);
        }

        function playEffectSound(effect) {
            if (effect === 'ladder') {
                [420, 560, 740].forEach((frequency, index) => playTone(frequency, 0.12, 'sine', 0.03, index * 0.07));
            } else if (effect === 'snake') {
                [420, 280, 180].forEach((frequency, index) => playTone(frequency, 0.14, 'sawtooth', 0.025, index * 0.08));
            } else if (effect === 'win') {
                [523, 659, 784, 1046].forEach((frequency, index) => playTone(frequency, 0.18, 'triangle', 0.04, index * 0.08));
            }
        }

        function createToken(playerIndex) {
            const token = document.createElement('span');
            token.className = 'token p' + (playerIndex + 1) + '-token';
            token.textContent = 'P' + (playerIndex + 1);
            tokenElements[playerIndex] = token;
        }

        function getTokenSlot(cellNumber) {
            const cell = board.querySelector('[data-cell="' + cellNumber + '"]');
            return cell ? cell.querySelector('.token-slot') : null;
        }

        function placeToken(playerIndex, cellNumber) {
            const slot = getTokenSlot(cellNumber);
            if (slot) {
                slot.appendChild(tokenElements[playerIndex]);
            }
        }

        function flashCell(cellNumber) {
            const cell = board.querySelector('[data-cell="' + cellNumber + '"]');
            if (!cell) {
                return;
            }

            cell.classList.remove('active-move');
            void cell.offsetWidth;
            cell.classList.add('active-move');
        }

        async function animateDice(rollValue) {
            const spinFrames = 8;
            for (let index = 0; index < spinFrames; index += 1) {
                diceFace.textContent = String((index % 6) + 1);
                await wait(60);
            }
            diceFace.textContent = String(rollValue);
        }

        async function animateMove(move) {
            const steppedTo = Math.min(move.stepped_to, 100);
            let stepCount = 0;

            for (let cell = move.from + 1; cell <= steppedTo; cell += 1) {
                stepCount += 1;
                placeToken(move.player, cell);
                flashCell(cell);
                playStepSound(stepCount);
                await wait(170);
            }

            if (move.to !== steppedTo) {
                playEffectSound(move.effect);
                await wait(180);
                placeToken(move.player, move.to);
                flashCell(move.to);
                await wait(260);
            }

            if (move.won) {
                playEffectSound('win');
            }
        }

        function renderInitialPositions() {
            createToken(0);
            createToken(1);
            placeToken(0, appState.positions[0]);
            placeToken(1, appState.positions[1]);
        }

        renderInitialPositions();

        rollForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            rollButton.disabled = true;

            try {
                await ensureAudioContext();
                playDiceSound();

                const response = await fetch('game.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'roll=1'
                });

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                const data = await response.json();
                if (!data.move) {
                    throw new Error('Move data missing');
                }

                await animateDice(data.last_roll);
                eventText.textContent = data.last_event;
                await animateMove(data.move);

                appState.positions = data.positions;
                appState.currentTurn = data.current_turn;

                if (data.winner) {
                    eventText.textContent = 'Player ' + data.winner + ' wins!';
                    await wait(500);
                    window.location.href = 'leaderboard.php?win=' + data.winner;
                    return;
                }

                turnIndicator.textContent = 'Player ' + (data.current_turn + 1);
            } catch (error) {
                HTMLFormElement.prototype.submit.call(rollForm);
                return;
            } finally {
                rollButton.disabled = false;
            }
        });
    </script>
</body>

</html>