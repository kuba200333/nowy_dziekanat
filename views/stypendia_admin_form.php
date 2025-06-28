<?php
// Plik: views/stypendia_admin_form.php

// Pobieramy listę lat akademickich do listy rozwijanej
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);
?>

<h1>Tworzenie Nowego Naboru na Stypendium</h1>
<p>Wypełnij poniższe pola, aby zdefiniować nowy okres, w którym studenci będą mogli składać wnioski o stypendium.</p>

<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="stworz_nabor">
    
    <div class="form-group">
        <label for="nazwa_naboru">Nazwa naboru (np. "Stypendium Rektora 2024/2025")</label>
        <input type="text" name="nazwa_naboru" id="nazwa_naboru" required>
    </div>

    <div class="form-group">
        <label for="typ_stypendium">Typ stypendium</label>
        <select name="typ_stypendium" id="typ_stypendium" required>
            <option value="rektora">Stypendium Rektora</option>
            <option value="socjalne">Stypendium Socjalne</option>
            <option value="inny">Inny</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="rok_akademicki_id">Rok akademicki, którego dotyczy nabór</label>
        <select name="rok_akademicki_id" id="rok_akademicki_id" required>
            <?php foreach ($roki as $rok): ?>
                <option value="<?= $rok['rok_akademicki_id'] ?>"><?= htmlspecialchars($rok['nazwa_roku']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="data_startu">Data rozpoczęcia naboru</label>
        <input type="datetime-local" name="data_startu" id="data_startu" required>
    </div>

    <div class="form-group">
        <label for="data_konca">Data zakończenia naboru</label>
        <input type="datetime-local" name="data_konca" id="data_konca" required>
    </div>
    
    <div class="form-group">
        <label for="status_naboru">Status naboru</label>
        <select name="status_naboru" id="status_naboru" required>
            <option value="otwarty">Otwarty</option>
            <option value="zamkniety">Zamknięty</option>
        </select>
    </div>

    <button type="submit">Utwórz Nabór</button>
</form>