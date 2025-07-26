<?php
// Plik: views/przedmioty_edit_form.php (Wersja z edycją wydziału)
if (!isset($_GET['przedmiot_id'])) {
    echo "<h1>Błąd</h1><p>Nie podano ID przedmiotu.</p>";
    return;
}
$przedmiot_id = (int)$_GET['przedmiot_id'];

$stmt = $conn->prepare("SELECT * FROM Przedmioty WHERE przedmiot_id = ?");
$stmt->bind_param("i", $przedmiot_id);
$stmt->execute();
$przedmiot = $stmt->get_result()->fetch_assoc();

if (!$przedmiot) {
    echo "<h1>Błąd</h1><p>Nie znaleziono przedmiotu.</p>";
    return;
}

// Pobieramy listę wszystkich wydziałów
$wydzialy = $conn->query("SELECT * FROM Wydzialy ORDER BY nazwa_wydzialu")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Edytuj Nazwę Przedmiotu</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="edit_przedmiot">
    <input type="hidden" name="przedmiot_id" value="<?= $przedmiot_id ?>">
    
    <div class="form-group">
        <label for="nazwa_przedmiotu">Nowa Nazwa Przedmiotu</label>
        <input type="text" id="nazwa_przedmiotu" name="nazwa_przedmiotu" value="<?= htmlspecialchars($przedmiot['nazwa_przedmiotu']) ?>" required>
    </div>
    
    <div class="form-group">
        <label for="wydzial_id">Zmień wydział:</label>
        <select name="wydzial_id" id="wydzial_id" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach($wydzialy as $wydzial): ?>
                <option value="<?= $wydzial['wydzial_id'] ?>" <?= ($przedmiot['wydzial_id'] == $wydzial['wydzial_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($wydzial['nazwa_wydzialu']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit">Zapisz Zmiany</button>
</form>