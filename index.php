<?php
// 1. Bring back the database connection
include "db.php"; 

// 2. HANDLE FORM SUBMISSION (MySQL Insert or Update)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add_runner') {
    
    // Clean and validate inputs
    $new_name = trim(htmlspecialchars($_POST['runner_name'] ?? 'Anonymous'));
    $new_min  = max(0, intval($_POST['minute'] ?? 0));
    $new_sec  = max(0, min(59, intval($_POST['second'] ?? 0)));
    $new_ms   = max(0, min(999, intval($_POST['milliseconds'] ?? 0)));

    // Calculate total milliseconds to evaluate which run is faster
    $new_total_ms = ($new_min * 60000) + ($new_sec * 1000) + $new_ms;

    /* This single SQL query handles both Add and Update:
      It tries to INSERT a new player. If the player's name already exists (DUPLICATE KEY),
      it triggers the UPDATE section, replacing the time ONLY IF the new total time is faster.
    */
    $sql = "INSERT INTO user (name, minute, second, milliseconds) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            minute = IF((minute*60000 + second*1000 + milliseconds) > ?, VALUES(minute), minute),
            second = IF((minute*60000 + second*1000 + milliseconds) > ?, VALUES(second), second),
            milliseconds = IF((minute*60000 + second*1000 + milliseconds) > ?, VALUES(milliseconds), milliseconds)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siiiiii", $new_name, $new_min, $new_sec, $new_ms, $new_total_ms, $new_total_ms, $new_total_ms);
    $stmt->execute();
    $stmt->close();

    // Post-Redirect-Get to avoid double form submissions
    header("Location: index.php");
    exit();
}

// 3. FETCH TOP 10 RANKINGS FROM MYSQL
$sql = "SELECT * FROM user ORDER BY minute ASC, second ASC, milliseconds ASC LIMIT 10";
$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Time Attack Leaderboard</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Racing+Sans+One&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="styles.css">
</head>
<body>
	<main class="page-shell">
		<section class="board-panel" aria-label="Top 10 leaderboard">
			<div class="board-header">
				<div>
					<p class="section-label">Top 10</p>
					<h2>Leaderboard</h2>
				</div>
				<div class="board-meta">Waiting for player data</div>
			</div>

			<div class="table-wrap" role="region" aria-label="Leaderboard entries">
				<table class="leaderboard-table">
					<thead>
						<tr>
							<th scope="col">Rank</th>
							<th scope="col">Player</th>
							<th scope="col">Time</th>
						</tr>
					</thead>
					<tbody>

					<?php
					$rank = 1;

					if($result->num_rows > 0){
						while($row = $result->fetch_assoc()){
							$time = str_pad($row["minute"],2,"0", STR_PAD_LEFT). "." .
									str_pad($row["second"],2,"0", STR_PAD_LEFT). "." .
									str_pad($row["milliseconds"],3,"0", STR_PAD_LEFT);

							echo "<tr>
									<td>" . str_pad($rank,2, "0", STR_PAD_LEFT) . "</td>
									<td>" . htmlspecialchars($row["Name"]) . "</td>
									<td>" . $time . "</td>
								</tr>";
							$rank++;
						}
						}else{
							for($i = 1; $i <= 10; $i++){
								echo"
								<tr>
									<td>" . str_pad($i,2,"0",STR_PAD_LEFT) . "</td>
									<td class='blank_cell'>-<td>
									<td class='blank_cell'>-<td>
								</tr>";
							}
						}
					?>
					</tbody>
				</table>
			</div>
		</section>
	</main>

	<details class="entry-bubble" aria-label="Dummy entry panel">
		<summary class="entry-bubble__summary">
			<span class="entry-bubble__icon" aria-hidden="true">+</span>
			<span class="entry-bubble__text">Entry</span>
		</summary>

		<div class="entry-bubble__panel">
			<div class="entry-bubble__header">
				<div>
					<p class="entry-bubble__label">Quick Entry</p>
					<h3>Add runner</h3>
				</div>
				<span class="entry-bubble__hint">dummy</span>
			</div>

			<form action="index.php" method="POST" class="entry-bubble__form">
                <input type="hidden" name="action" value="add_runner">

                <label class="sr-only" for="runner-name">Runner name</label>
                <input id="runner-name" name="runner_name" class="entry-input entry-input--wide" type="text" placeholder="Runner name" aria-label="Runner name" required>

                <div class="entry-bubble__time">
                    <label class="sr-only" for="minutes">Minutes</label>
                    <input id="minutes" name="minute" class="entry-input" type="text" inputmode="numeric" placeholder="00" aria-label="Minutes" required>
                    
                    <label class="sr-only" for="seconds">Seconds</label>
                    <input id="seconds" name="second" class="entry-input" type="text" inputmode="numeric" placeholder="00" aria-label="Seconds" required>
                    
                    <label class="sr-only" for="milliseconds">Milliseconds</label>
                    <input id="milliseconds" name="milliseconds" class="entry-input" type="text" inputmode="numeric" placeholder="000" aria-label="Milliseconds" required>
                </div>

                <button class="entry-button" type="submit">Queue entry</button>
            </form>
		</div>
	</details>
</body>
</html>
