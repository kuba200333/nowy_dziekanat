<?php
// Plik: views/grupy_edit_form.php
if (!isset($_GET['grupa_id'])) {
    echo "<h1>Błąd</h1><p>Nie podano ID grupy.</p>";
    return;
}
$grupa_id = (int)$_GET['grupa_id'];

// Pobieramy aktualne dane grupy do edycji
$stmt = $conn->prepare("SELECT * FROM GrupyZajeciowe WHERE grupa_id = ?");
$stmt->bind_param("i", $grupa_id);
$stmt->execute();
$grupa = $stmt->get_result()->fetch_assoc();

if (!$grupa) {
    echo "<h1>Błąd</h1><p>Nie znaleziono grupy.</p>";
    return;
}

// Pobieramy listę lat akademickich do listy rozwijanej
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Edytuj Grupę Zajęciową</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="edit_grupa">
    <input type="hidden" name="grupa_id" value="<?= $grupa_id ?>">
    
    <div class="form-group">
        <label for="nazwa_grupy">Nazwa Grupy</label>
        <input type="text" id="nazwa_grupy" name="nazwa_grupy" value="<?= htmlspecialchars($grupa['nazwa_grupy']) ?>" required>
    </div>
    <div class="form-group">
        <label for="semestr">Semestr</label>
        <input type="number" id="semestr" name="semestr" value="<?= htmlspecialchars($grupa['semestr']) ?>" required min="1">
    </div>
    <div class="form-group">
        <label for="rok_akademicki_id">Rok Akademicki</label>
        <select id="rok_akademicki_id" name="rok_akademicki_id" required>
            <?php foreach ($roki as $rok): ?>
                <option value="<?= $rok['rok_akademicki_id'] ?>" <?= ($grupa['rok_akademicki_id'] == $rok['rok_akademicki_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($rok['nazwa_roku']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit">Zapisz Zmiany</button>
</form>