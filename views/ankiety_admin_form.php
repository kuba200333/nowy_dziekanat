<?php
// Plik: views/ankiety_admin_form.php
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Tworzenie Nowej Ankiety</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="stworz_ankiete">
    <div class="form-group"><label for="nazwa_ankiety">Nazwa ankiety (np. "Ankietyzacja semestr zimowy 2024/25")</label><input type="text" name="nazwa_ankiety" id="nazwa_ankiety" required></div>
    <div class="form-group"><label for="rok_akademicki_id">Rok akademicki</label><select name="rok_akademicki_id" required><?php foreach ($roki as $rok): ?><option value="<?= $rok['rok_akademicki_id'] ?>"><?= htmlspecialchars($rok['nazwa_roku']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="semestr">Semestr, którego dotyczy ankieta</label><input type="number" name="semestr" id="semestr" required min="1"></div>
    <div class="form-group"><label for="data_startu">Data rozpoczęcia</label><input type="datetime-local" name="data_startu" id="data_startu" required></div>
    <div class="form-group"><label for="data_konca">Data zakończenia</label><input type="datetime-local" name="data_konca" id="data_konca" required></div>
    <button type="submit">Utwórz Ankietę</button>
</form>