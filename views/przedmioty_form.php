<?php
// Plik: views/przedmioty_form.php (Wersja z wyborem wydziału)

// Pobieramy listę wydziałów do listy rozwijanej
$wydzialy = $conn->query("SELECT * FROM Wydzialy ORDER BY nazwa_wydzialu")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Dodaj Nowy Przedmiot</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="add_przedmiot">
    
    <div class="form-group">
        <label for="nazwa_przedmiotu">Nazwa Przedmiotu</label>
        <input type="text" id="nazwa_przedmiotu" name="nazwa_przedmiotu" required>
    </div>
    
    <div class="form-group">
        <label for="wydzial_id">Wybierz wydział:</label>
        <select name="wydzial_id" id="wydzial_id" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach($wydzialy as $wydzial): ?>
                <option value="<?= $wydzial['wydzial_id'] ?>"><?= htmlspecialchars($wydzial['nazwa_wydzialu']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit">Dodaj Przedmiot</button>
</form>