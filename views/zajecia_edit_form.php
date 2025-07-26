<?php
// Plik: views/zajecia_edit_form.php
if (!isset($_GET['zajecia_id'])) {
    echo "<h1>Błąd</h1><p>Nie podano ID zajęć.</p>";
    return;
}
$zajecia_id = (int)$_GET['zajecia_id'];

// Pobieramy aktualne dane zajęć
$stmt = $conn->prepare("SELECT * FROM Zajecia WHERE zajecia_id = ?");
$stmt->bind_param("i", $zajecia_id);
$stmt->execute();
$zajecia = $stmt->get_result()->fetch_assoc();

if (!$zajecia) {
    echo "<h1>Błąd</h1><p>Nie znaleziono zajęć.</p>";
    return;
}

// Pobieramy listy do dropdownów
$prowadzacy_lista = $conn->query("SELECT * FROM Prowadzacy ORDER BY nazwisko")->fetch_all(MYSQLI_ASSOC);
$grupy_lista = $conn->query("SELECT * FROM GrupyZajeciowe ORDER BY nazwa_grupy")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Edytuj Zajęcia</h1>
<p>Możesz tutaj zmienić grupę lub przypisać/zmienić prowadzącego dla istniejących zajęć.</p>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="edit_zajecia">
    <input type="hidden" name="zajecia_id" value="<?= $zajecia_id ?>">
    
    <div class="form-group">
        <label for="grupa_id">Grupa</label>
        <select name="grupa_id" id="grupa_id" required>
            <?php foreach ($grupy_lista as $grupa): ?>
                <option value="<?= $grupa['grupa_id'] ?>" <?= ($zajecia['grupa_id'] == $grupa['grupa_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($grupa['nazwa_grupy']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="prowadzacy_id">Prowadzący</label>
        <select name="prowadzacy_id" id="prowadzacy_id">
            <option value="">-- Brak przypisania --</option>
            <?php foreach ($prowadzacy_lista as $prowadzacy): ?>
                <option value="<?= $prowadzacy['prowadzacy_id'] ?>" <?= ($zajecia['prowadzacy_id'] == $prowadzacy['prowadzacy_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($prowadzacy['tytul_naukowy'] . ' ' . $prowadzacy['imie'] . ' ' . $prowadzacy['nazwisko']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <button type="submit">Zapisz Zmiany</button>
</form>