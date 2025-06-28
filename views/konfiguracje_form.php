<?php
// Plik: views/konfiguracje_form.php
$przedmioty = $conn->query("SELECT * FROM Przedmioty ORDER BY nazwa_przedmiotu")->fetch_all(MYSQLI_ASSOC);
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Skonfiguruj Przedmiot na Rok Akademicki</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="add_konfiguracja">
    <div class="form-group">
        <label for="k_przedmiot_id">Przedmiot</label>
        <select id="k_przedmiot_id" name="przedmiot_id" required>
            <?php foreach ($przedmioty as $przedmiot): ?>
                <option value="<?= $przedmiot['przedmiot_id'] ?>"><?= htmlspecialchars($przedmiot['nazwa_przedmiotu']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="k_rok_id">Rok Akademicki</label>
        <select id="k_rok_id" name="rok_akademicki_id" required>
            <?php foreach ($roki as $rok): ?>
                <option value="<?= $rok['rok_akademicki_id'] ?>"><?= htmlspecialchars($rok['nazwa_roku']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="punkty_ects">Punkty ECTS</label>
        <input type="number" id="punkty_ects" name="punkty_ects" required min="0">
    </div>
    <button type="submit">Zapisz Konfigurację</button>
</form>