<?php
// Plik: views/plan_modyfikuj_termin.php

// Pobieramy dane do list rozwijanych
// 1. Wszystkie zaplanowane terminy
$terminy_sql = "
    SELECT 
        t.termin_id,
        p.nazwa_przedmiotu, 
        z.forma_zajec, 
        g.nazwa_grupy,
        t.data_zajec,
        t.godzina_rozpoczecia
    FROM TerminyZajec t
    JOIN Zajecia z ON t.zajecia_id = z.zajecia_id
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    ORDER BY t.data_zajec, t.godzina_rozpoczecia
";
$terminy_lista = $conn->query($terminy_sql)->fetch_all(MYSQLI_ASSOC);

// 2. Wszyscy prowadzący (do listy zastępstw)
$prowadzacy_lista = $conn->query("SELECT prowadzacy_id, imie, nazwisko, tytul_naukowy FROM Prowadzacy ORDER BY nazwisko")->fetch_all(MYSQLI_ASSOC);

// 3. Wszystkie sale
$sale_lista = $conn->query("SELECT sala_id, budynek, numer_sali FROM SaleZajeciowe ORDER BY budynek, numer_sali")->fetch_all(MYSQLI_ASSOC);
?>

<h1>Modyfikacja Terminu Zajęć (Zastępstwa / Odwołania)</h1>
<p>Wybierz konkretne zajęcia z planu, aby zmienić ich status, przydzielić zastępstwo lub przenieść je do innej sali.</p>

<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="modyfikuj_termin">

    <div class="form-group">
        <label for="termin_id">Wybierz termin do modyfikacji</label>
        <select id="termin_id" name="termin_id" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach ($terminy_lista as $termin): ?>
                <option value="<?= $termin['termin_id'] ?>">
                    <?= htmlspecialchars(
                        $termin['data_zajec'] . ' ' . 
                        substr($termin['godzina_rozpoczecia'], 0, 5) . ' | ' .
                        $termin['nazwa_przedmiotu'] . ' - ' . 
                        $termin['forma_zajec'] . ' (' . $termin['nazwa_grupy'] . ')'
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <hr>
    <h3>Nowe ustawienia dla wybranego terminu:</h3>

    <div class="form-group">
        <label for="status">Zmień status zajęć</label>
        <select id="status" name="status" required>
            <option value="planowe">Planowe (bez zmian)</option>
            <option value="odwolane">Odwołane</option>
            <option value="zastepstwo">Zastępstwo</option>
            <option value="rektorskie">Rektorskie</option>
        </select>
    </div>

    <div class="form-group">
        <label for="prowadzacy_id">Nowy prowadzący (na zastępstwo)</label>
        <select id="prowadzacy_id" name="prowadzacy_id">
            <option value="">-- Bez zmian --</option>
            <?php foreach ($prowadzacy_lista as $prowadzacy): ?>
                <option value="<?= $prowadzacy['prowadzacy_id'] ?>">
                    <?= htmlspecialchars($prowadzacy['tytul_naukowy'] . ' ' . $prowadzacy['imie'] . ' ' . $prowadzacy['nazwisko']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="sala_id">Nowa sala</label>
        <select id="sala_id" name="sala_id">
            <option value="">-- Bez zmian --</option>
             <?php foreach ($sale_lista as $sala): ?>
                <option value="<?= $sala['sala_id'] ?>">
                    <?= htmlspecialchars($sala['budynek'] . ' - ' . $sala['numer_sali']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group full-width">
        <label for="notatki">Notatki (np. "Zajęcia odwołane z powodu choroby prowadzącego")</label>
        <input type="text" id="notatki" name="notatki" placeholder="Wpisz opcjonalną informację">
    </div>


    <button type="submit">Zapisz Zmiany w Terminie</button>
</form>