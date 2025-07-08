<?php
// Plik: views/zajecia_form.php (Wersja z opcjonalnym prowadzącym)
$konfiguracje = $conn->query("SELECT k.konfiguracja_id, p.nazwa_przedmiotu, r.nazwa_roku FROM KonfiguracjaPrzedmiotu k JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id JOIN RokiAkademickie r ON k.rok_akademicki_id = r.rok_akademicki_id ORDER BY p.nazwa_przedmiotu")->fetch_all(MYSQLI_ASSOC);
$prowadzacy = $conn->query("SELECT * FROM Prowadzacy ORDER BY nazwisko")->fetch_all(MYSQLI_ASSOC);
$grupy = $conn->query("SELECT * FROM GrupyZajeciowe ORDER BY nazwa_grupy")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Utwórz Nowe Zajęcia</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="add_zajecia">
    <div class="form-group"><label for="konfiguracja_id">Przedmiot (w danym roku)</label><select id="konfiguracja_id" name="konfiguracja_id" required><?php foreach ($konfiguracje as $konf): ?><option value="<?= $konf['konfiguracja_id'] ?>"><?= htmlspecialchars($konf['nazwa_przedmiotu'] . ' (' . $konf['nazwa_roku'] . ')') ?></option><?php endforeach; ?></select></div>
    <div class="form-group">
        <label for="prowadzacy_id">Prowadzący (opcjonalnie)</label>
        <select id="prowadzacy_id" name="prowadzacy_id">
            <option value="">-- Brak przypisania --</option>
            <?php foreach ($prowadzacy as $p): ?>
                <option value="<?= $p['prowadzacy_id'] ?>"><?= htmlspecialchars($p['tytul_naukowy'] . ' ' . $p['imie'] . ' ' . $p['nazwisko']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><label for="grupa_id_zajecia">Grupa</label><select id="grupa_id_zajecia" name="grupa_id" required><?php foreach ($grupy as $grupa): ?><option value="<?= $grupa['grupa_id'] ?>"><?= htmlspecialchars($grupa['nazwa_grupy']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="forma_zajec">Forma Zajęć</label><select id="forma_zajec" name="forma_zajec" required><option value="Wykład">Wykład</option><option value="Laboratorium">Laboratorium</option><option value="Audytoryjne">Audytoryjne</option><option value="Projekt">Projekt</option><option value="Seminarium">Seminarium</option><option value="Lektorat">Lektorat</option></select></div>
    <button type="submit">Utwórz Zajęcia i Zapisz Grupę</button>
</form>