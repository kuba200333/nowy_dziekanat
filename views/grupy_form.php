<?php
// Plik: views/grupy_form.php
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Dodaj Nową Grupę Zajęciową</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="add_grupa">
    <div class="form-group">
        <label for="nazwa_grupy">Nazwa Grupy</label>
        <input type="text" id="nazwa_grupy" name="nazwa_grupy" required placeholder="np. I rok, L2">
    </div>
    <div class="form-group">
        <label for="semestr">Semestr</label>
        <input type="number" id="semestr" name="semestr" required min="1">
    </div>
    <div class="form-group">
        <label for="rok_akademicki_id">Rok Akademicki</label>
        <select id="rok_akademicki_id" name="rok_akademicki_id" required>
            <option value="">-- Wybierz rok --</option>
            <?php foreach ($roki as $rok): ?>
                <option value="<?= $rok['rok_akademicki_id'] ?>"><?= htmlspecialchars($rok['nazwa_roku']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit">Dodaj Grupę</button>
</form>