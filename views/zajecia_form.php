<?php
// Plik: views/zajecia_form.php
$konfiguracje = $conn->query("SELECT k.konfiguracja_id, p.nazwa_przedmiotu, r.nazwa_roku FROM KonfiguracjaPrzedmiotu k JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id JOIN RokiAkademickie r ON k.rok_akademicki_id = r.rok_akademicki_id ORDER BY p.nazwa_przedmiotu")->fetch_all(MYSQLI_ASSOC);
$prowadzacy = $conn->query("SELECT * FROM Prowadzacy ORDER BY nazwisko")->fetch_all(MYSQLI_ASSOC);
$grupy = $conn->query("SELECT * FROM GrupyZajeciowe gz JOIN rokiakademickie ra on gz.rok_akademicki_id= ra.rok_akademicki_id ORDER BY ra.nazwa_roku, gz.semestr, gz.nazwa_grupy")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Utwórz Nowe Zajęcia</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="add_zajecia">
    <div class="form-group">
        <label for="konfiguracja_id">Przedmiot (w danym roku)</label>
        <select id="konfiguracja_id" name="konfiguracja_id" required>
            <option value="">-- Wybierz skonfigurowany przedmiot --</option>
            <?php foreach ($konfiguracje as $konf): ?>
                <option value="<?= $konf['konfiguracja_id'] ?>"><?= htmlspecialchars($konf['nazwa_przedmiotu'] . ' (' . $konf['nazwa_roku'] . ')') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="prowadzacy_id">Prowadzący</label>
        <select id="prowadzacy_id" name="prowadzacy_id" required>
             <option value="">-- Wybierz prowadzącego --</option>
            <?php foreach ($prowadzacy as $p): ?>
                <option value="<?= $p['prowadzacy_id'] ?>"><?= htmlspecialchars($p['tytul_naukowy'] . ' ' . $p['imie'] . ' ' . $p['nazwisko']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="grupa_id">Grupa</label>
        <select id="grupa_id" name="grupa_id" required>
             <option value="">-- Wybierz grupę --</option>
             <?php foreach ($grupy as $grupa): ?>
                <option value="<?= $grupa['grupa_id'] ?>"><?= htmlspecialchars($grupa['nazwa_grupy'])?><?= htmlspecialchars(" (Rok: ".$grupa['nazwa_roku'])?><?= htmlspecialchars(" , semestr: ".$grupa['semestr'].")")?></option>
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
        <label for="waga_oceny">Waga Oceny (0.00 - 1.00)</label>
        <input type="number" step="0.01" min="0" max="1" id="waga_oceny" name="waga_oceny" required>
    </div>
    <button type="submit">Utwórz Zajęcia</button>
</form>