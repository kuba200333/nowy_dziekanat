<?php
// Plik: ustaw_haslo.php - Narzędzie do ustawiania haseł dla istniejących użytkowników

require_once 'db_config.php';
$message = '';
$status_class = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = (int)$_POST['user_id'];
    $new_password = $_POST['new_password'];
    $role = $_POST['role'];

    if (empty($user_id) || empty($new_password)) {
        $message = "ID użytkownika i hasło nie mogą być puste.";
        $status_class = 'error';
    } else {
        // KROK KLUCZOWY: Hashowanie hasła przed zapisem do bazy
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        if ($role == 'student') {
            $sql = "UPDATE Studenci SET haslo = ? WHERE numer_albumu = ?";
        } else { // 'pracownik'
            $sql = "UPDATE Prowadzacy SET haslo = ? WHERE prowadzacy_id = ?";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $message = "Hasło dla użytkownika o ID: $user_id zostało pomyślnie ustawione!";
                $status_class = 'success';
            } else {
                $message = "Nie znaleziono użytkownika o podanym ID: $user_id.";
                $status_class = 'error';
            }
        } else {
            $message = "Wystąpił błąd podczas aktualizacji: " . $stmt->error;
            $status_class = 'error';
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Ustawianie Hasła</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { max-width: 500px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Narzędzie do Ustawiania Haseł</h1>
        <p style="text-align: center; background: #fffbe6; padding: 10px; border-radius: 5px; border: 1px solid #ffe58f;">
            <strong>Uwaga:</strong> To jest narzędzie administracyjne. Po ustawieniu potrzebnych haseł usuń ten plik z serwera.
        </p>

        <?php if(!empty($message)): ?>
            <p class="status-message <?= $status_class ?>"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" action="ustaw_haslo.php">
            <div class="form-group">
                <label for="role">Rola użytkownika:</label>
                <select name="role" id="role" required>
                    <option value="student">Student</option>
                    <option value="pracownik">Pracownik</option>
                </select>
            </div>
            <div class="form-group">
                <label for="user_id">ID Użytkownika (Numer albumu / ID pracownika)</label>
                <input type="text" name="user_id" id="user_id" required>
            </div>
            <div class="form-group">
                <label for="new_password">Nowe Hasło</label>
                <input type="text" name="new_password" id="new_password" required>
            </div>
            <button type="submit">Ustaw Hasło</button>
        </form>
        <br>
        <a href="login.php" style="text-align:center; display:block;">Wróć do strony logowania</a>
    </div>
</body>
</html>