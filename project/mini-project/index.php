<?php
session_start();

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: 04_login_exercise.php');
    exit;
}

$conn = mysqli_connect("localhost", "root", "", "login_auth");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ---------- read the form (defaults on first visit) ----------
$smoking   = $_POST['smoking']     ?? 'non-smoker';
$drinking  = $_POST['drinking']    ?? 'non-drinker';
$smokeFreq = (int)($_POST['smoke_freq'] ?? 0);
$drinkFreq = (int)($_POST['drink_freq'] ?? 0);
$heightCm  = (float)($_POST['height_cm'] ?? 0);
$weightKg  = (float)($_POST['weight_kg'] ?? 0);
$age       = (int)($_POST['age'] ?? 0);
$sleepHrs  = (float)($_POST['sleep_hours'] ?? 0);

$advice = [];

// ---------- build the advice (only after submit) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // BMI = kg / m*m
    if ($heightCm > 0 && $weightKg > 0) {
        $m   = $heightCm / 100;
        $bmi = round($weightKg / ($m * $m), 1);

        if ($bmi < 18.5) {
            $advice[] = "Your BMI is $bmi (underweight). To gain: calorie-dense food (nuts, dairy, rice), protein at every meal, strength training.";
        } elseif ($bmi < 25) {
            $advice[] = "Your BMI is $bmi - healthy range, keep it up!";
        } elseif ($bmi < 30) {
            $advice[] = "Your BMI is $bmi (overweight). To lose: walk more, cut sugary drinks, more vegetables.";
        } else {
            $advice[] = "Your BMI is $bmi (obese). Start with small daily walks and consider professional guidance.";
        }
    }

    if ($smoking === 'smoker') {
        $advice[] = "Smoking $smokeFreq x/week raises the risk of lung cancer and heart disease. Try cutting down a little each week.";
    }
    if ($drinking === 'drinker') {
        $advice[] = "Drinking $drinkFreq x/week can damage your liver and your sleep. Try to reduce gradually.";
    }

    // sleep rounded per hour; rule depends on age
    $sleep = round($sleepHrs);
    if ($age <= 21 && $sleep < 9) {
        $advice[] = "You get about $sleep h of sleep - at your age you need 9 h+. Sleep early: fixed bedtime, no screens 1 h before bed, no caffeine after noon.";
    } elseif ($age > 21 && $sleep < 7) {
        $advice[] = "You get about $sleep h of sleep - aim for at least 7 h. A fixed bedtime helps.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HealthGauge</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css" type="text/css">
</head>
<body>

<div class="container my-4">

   <h1 class="hg-header">HealthGauge</h1>
   <p>Welcome! <a href="05_logout_exercise.php">Log out</a></p>

</div>

<form method="post" class="container my-3">

    <label>Do you smoke?
        <select name="smoking" onchange="showFreq(this, 'smoke_box', 'smoker')">
            <option value="non-smoker" <?php if ($smoking === 'non-smoker') echo 'selected'; ?>>Non-smoker</option>
            <option value="smoker" <?php if ($smoking === 'smoker') echo 'selected'; ?>>Smoker</option>
        </select>
    </label>
    <div id="smoke_box" style="<?php echo ($smoking === 'smoker') ? '' : 'display:none;'; ?>">
        <label>How often? Each week?
            <input type="number" name="smoke_freq" min="0" value="<?php echo $smokeFreq ?: ''; ?>">
        </label>
    </div>
    <br><br>

    <label>Do you drink alcohol?
        <select name="drinking" onchange="showFreq(this, 'drink_box', 'drinker')">
            <option value="non-drinker" <?php if ($drinking === 'non-drinker') echo 'selected'; ?>>Non-drinker</option>
            <option value="drinker" <?php if ($drinking === 'drinker') echo 'selected'; ?>>Drinker</option>
        </select>
    </label>

    <div id="drink_box" style="<?php echo ($drinking === 'drinker') ? '' : 'display:none;'; ?>">
        <label>How often? Each week?
            <input type="number" name="drink_freq" min="0" value="<?php echo $drinkFreq ?: ''; ?>">
        </label>
    </div>
    <br><br>

    <hr>

    <label>Height (cm) <input type="number" name="height_cm" min="0" value="<?php echo $heightCm ?: ''; ?>"></label>
    <br><br>
    <label>Weight (kg) <input type="number" name="weight_kg" min="0" step="0.1" value="<?php echo $weightKg ?: ''; ?>"></label>
    <br><br>

    <label>Your age <input type="number" name="age" min="0" value="<?php echo $age ?: ''; ?>"></label>
    <br><br>
    <label>What is your sleep schedule? Hours per day:
        <input type="number" name="sleep_hours" min="0" max="24" step="0.5" value="<?php echo $sleepHrs ?: ''; ?>">
    </label>
    <br><br>

    <button type="submit">Check my health</button>
</form>

<?php if (!empty($advice)): ?>
    <h2>Your results</h2>
    <ul>
        <?php foreach ($advice as $line): ?>
            <li><?php echo $line; ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<script>
function showFreq(selectEl, boxId, showWhen) {
    document.getElementById(boxId).style.display =
        (selectEl.value === showWhen) ? 'block' : 'none';
}
</script>

</body>
</html>