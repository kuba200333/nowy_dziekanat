<?php
// Plik: views/prowadzacy_edit_form.php
if (!isset($_GET['prowadzacy_id'])) {
    echo "<h1>Błąd</h1><p>Nie podano ID prowadzącego.</p>";
    return;
}
$prowadzacy_id = (int)$_GET['prowadzacy_id'];

$stmt = $conn->prepare("SELECT * FROM Prowadzacy WHERE prowadzacy_id = ?");
$stmt->bind_param("i", $prowadzacy_id);
$stmt->execute();
$prowadzacy = $stmt->get_result()->fetch_assoc();

if (!$prowadzacy) {
    echo "<h1>Błąd</h1><p>Nie znaleziono prowadzącego.</p>";
    return;
}
?>
<h1>Edytuj Dane Prowadzącego</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="edit_prowadzacy">
    <input type="hidden" name="prowadzacy_id" value="<?= $prowadzacy_id ?>">
    
    <div class="form-group">
        <label for="p_imie">Imię</label>
        <input type="text" id="p_imie" name="imie" value="<?= htmlspecialchars($prowadzacy['imie']) ?>" required>
    </div>
    <div class="form-group">
        <label for="p_nazwisko">Nazwisko</label>
        <input type="text" id="p_nazwisko" name="nazwisko" value="<?= htmlspecialchars($prowadzacy['nazwisko']) ?>" required>
    </div>
    <div class="form-group">
        <label for="p_tytul">Tytuł Naukowy</label>
        <input type="text" id="p_tytul" name="tytul_naukowy" value="<?= htmlspecialchars($prowadzacy['tytul_naukowy']) ?>">
    </div>
    <div class="form-group">
        <label for="p_email">Email</label>
        <input type="email" id="p_email" name="email" value="<?= htmlspecialchars($prowadzacy['email']) ?>" required>
    </div>
    <div class="form-group">
        <label for="is_admin">Uprawnienia:</label>
        <select name="is_admin" id="is_admin">
            <option value="0" <?= (!$prowadzacy['is_admin']) ? 'selected' : '' ?>>Zwykły nauczyciel</option>
            <option value="1" <?= ($prowadzacy['is_admin']) ? 'selected' : '' ?>>Administrator</option>
        </select>
    </div>
    <button type="submit">Zapisz Zmiany</button>
</form>