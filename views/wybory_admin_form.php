<?php
// Plik: views/wybory_admin_form.php
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);
$przedmioty = $conn->query("SELECT * FROM Przedmioty ORDER BY nazwa_przedmiotu")->fetch_all(MYSQLI_ASSOC);
$grupy = $conn->query("SELECT * FROM GrupyZajeciowe ORDER BY nazwa_grupy")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Tworzenie Nowego Wyboru Przedmiotów Obieralnych</h1>

<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="stworz_wybory">
    
    <div class="form-group">
        <label for="nazwa_wyboru">Nazwa wydarzenia (np. "Wybór elektywu na semestr 5")</label>
        <input type="text" name="nazwa_wyboru" id="nazwa_wyboru" required>
    </div>
    
    <div class="form-group">
        <label for="rok_akademicki_id">Rok akademicki</label>
        <select name="rok_akademicki_id" required>
            <?php foreach ($roki as $rok): ?>
                <option value="<?= $rok['rok_akademicki_id'] ?>"><?= htmlspecialchars($rok['nazwa_roku']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="semestr">Semestr, którego dotyczy wybór</label>
        <input type="number" name="semestr" id="semestr" required min="1">
    </div>

    <div class="form-group">
        <label for="data_rozpoczecia">Data rozpoczęcia wyboru</label>
        <input type="datetime-local" name="data_rozpoczecia" id="data_rozpoczecia" required>
    </div>

    <div class="form-group">
        <label for="data_zakonczenia">Data zakończenia wyboru</label>
        <input type="datetime-local" name="data_zakonczenia" id="data_zakonczenia" required>
    </div>

    <hr>
    
    <div class="form-group">
        <label>Przedmioty do wyboru (zaznacz kilka):</label>
        <select name="przedmioty_ids[]" multiple required size="8" style="height: auto;">
            <?php foreach ($przedmioty as $przedmiot): ?>
                <option value="<?= $przedmiot['przedmiot_id'] ?>"><?= htmlspecialchars($przedmiot['nazwa_przedmiotu']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Grupy docelowe (dla kogo jest ten wybór):</label>
        <select name="grupy_ids[]" multiple required size="8" style="height: auto;">
            <?php foreach ($grupy as $grupa): ?>
                <option value="<?= $grupa['grupa_id'] ?>"><?= htmlspecialchars($grupa['nazwa_grupy']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <button type="submit">Stwórz wydarzenie wyboru</button>
</form>