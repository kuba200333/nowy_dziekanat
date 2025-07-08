<?php
// Plik: views/konfiguracje_komponentow_form.php

$konfiguracje = $conn->query("SELECT k.konfiguracja_id, p.nazwa_przedmiotu, r.nazwa_roku FROM KonfiguracjaPrzedmiotu k JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id JOIN RokiAkademickie r ON k.rok_akademicki_id = r.rok_akademicki_id ORDER BY p.nazwa_przedmiotu")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Zarządzanie Wagami Komponentów</h1>
<p>W tym miejscu zdefiniuj wagi dla poszczególnych form zajęć w ramach przedmiotu na dany rok akademicki.</p>

<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="zapisz_konfiguracje_komponentu">
    
    <div class="form-group">
        <label for="konfiguracja_id">Wybierz przedmiot (w danym roku)</label>
        <select id="konfiguracja_id" name="konfiguracja_id" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach ($konfiguracje as $konf): ?>
                <option value="<?= $konf['konfiguracja_id'] ?>"><?= htmlspecialchars($konf['nazwa_przedmiotu'] . ' (' . $konf['nazwa_roku'] . ')') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="forma_zajec">Forma Zajęć</label>
        <select id="forma_zajec" name="forma_zajec" required>
            <option value="Wykład">Wykład</option>
            <option value="Laboratorium">Laboratorium</option>
            <option value="Audytoryjne">Audytoryjne</option>
            <option value="Projekt">Projekt</option>
            <option value="Seminarium">Seminarium</option>
            <option value="Lektorat">Lektorat</option>
        </select>
    </div>
    <div class="form-group">
        <label for="waga_oceny">Waga Oceny (np. 0.40)</label>
        <input type="number" step="0.01" min="0" max="1" id="waga_oceny" name="waga_oceny" required>
    </div>
    <button type="submit">Zapisz Wagę</button>
</form>