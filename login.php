<?php
// Plik: login.php
session_start();
require_once 'db_config.php';
$error_message = '';

// Jeśli formularz został wysłany
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_id = $_POST['login_id'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if ($role == 'student') {
        $stmt = $conn->prepare("SELECT numer_albumu, haslo FROM Studenci WHERE numer_albumu = ?");
        $stmt->bind_param("i", $login_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Sprawdzamy hashowane hasło
            if (password_verify($password, $user['haslo'])) {
                $_SESSION['user_id'] = $user['numer_albumu'];
                $_SESSION['role'] = 'student';
                header("Location: index.php");
                exit();
            }
        }
    }   elseif ($role == 'pracownik') {
        // ZMIANA: Pobieramy teraz również status admina
        $stmt = $conn->prepare("SELECT prowadzacy_id, haslo, is_admin FROM Prowadzacy WHERE prowadzacy_id = ?");
        $stmt->bind_param("i", $login_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['haslo'])) {
                $_SESSION['user_id'] = $user['prowadzacy_id'];
                $_SESSION['role'] = 'pracownik';
                // ZMIANA: Zapisujemy status admina do sesji
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                header("Location: index.php");
                exit();
            }
        }
    }
    $error_message = 'Nieprawidłowy login lub hasło!';
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logowanie - Wirtualny Dziekanat</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-container { max-width: 400px; width: 100%; }
        .form-group { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container login-container">
        <h1>Logowanie do systemu</h1>
        
        <?php if(!empty($error_message)): ?>
            <p class="status-message error"><?= $error_message ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="role">Jestem:</label>
                <select name="role" id="role" required>
                    <option value="student">Studentem</option>
                    <option value="pracownik">Pracownikiem</option>
                </select>
            </div>
            <div class="form-group">
                <label for="login_id">ID (Numer albumu / ID pracownika)</label>
                <input type="text" name="login_id" id="login_id" required>
            </div>
            <div class="form-group">
                <label for="password">Hasło</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">Zaloguj się</button>
        </form>
    </div>
</body>
</html>