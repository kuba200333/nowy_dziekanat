<?php
// Plik: views/plan_dodaj_szablon.php

// Pobieramy dane do list rozwijanych
$zajecia_sql = "
    SELECT z.zajecia_id, p.nazwa_przedmiotu, z.forma_zajec, g.nazwa_grupy
    FROM Zajecia z
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    ORDER BY p.nazwa_przedmiotu, g.nazwa_grupy";
$zajecia_lista = $conn->query($zajecia_sql)->fetch_all(MYSQLI_ASSOC);

$sale = $conn->query("SELECT sala_id, budynek, numer_sali FROM SaleZajeciowe ORDER BY budynek, numer_sali")->fetch_all(MYSQLI_ASSOC);
?>

<h1>Tworzenie Szablonu Planu Zajęć</h1>
<p>Zdefiniuj cykliczne zajęcia. Po zapisaniu szablonu system będzie mógł wygenerować na jego podstawie konkretne terminy w kalendarzu.</p>

<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="dodaj_szablon_planu">
    
    <div class="form-group">
        <label for="zajecia_id">Wybierz zajęcia</label>
        <select id="zajecia_id" name="zajecia_id" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach ($zajecia_lista as $zajecia): ?>
                <option value="<?= $zajecia['zajecia_id'] ?>">
                    <?= htmlspecialchars($zajecia['nazwa_przedmiotu'] . ' - ' . $zajecia['forma_zajec'] . ' (' . $zajecia['nazwa_grupy'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label for="sala_id">Wybierz salę</label>
        <select id="sala_id" name="sala_id" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach ($sale as $sala): ?>
                <option value="<?= $sala['sala_id'] ?>">
                    <?= htmlspecialchars($sala['budynek'] . ' - ' . $sala['numer_sali']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="dzien_tygodnia">Dzień tygodnia</label>
        <select id="dzien_tygodnia" name="dzien_tygodnia" required>
            <option value="1">Poniedziałek</option>
            <option value="2">Wtorek</option>
            <option value="3">Środa</option>
            <option value="4">Czwartek</option>
            <option value="5">Piątek</option>
            <option value="6">Sobota</option>
            <option value="7">Niedziela</option>
        </select>
    </div>

    <div class="form-group">
        <label for="typ_cyklu">Cykliczność zajęć</label>
        <select id="typ_cyklu" name="typ_cyklu" required>
            <option value="tygodniowy">Co tydzień</option>
            <option value="dwutyg_parzysty">Co 2 tygodnie (w tyg. parzyste)</option>
            <option value="dwutyg_nieparzysty">Co 2 tygodnie (w tyg. nieparzyste)</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="godzina_rozpoczecia">Godzina rozpoczęcia</label>
        <input type="time" id="godzina_rozpoczecia" name="godzina_rozpoczecia" required>
    </div>

    <div class="form-group">
        <label for="godzina_zakonczenia">Godzina zakończenia</label>
        <input type="time" id="godzina_zakonczenia" name="godzina_zakonczenia" required>
    </div>

    <div class="form-group">
        <label for="data_startu_cyklu">Zajęcia od dnia</label>
        <input type="date" id="data_startu_cyklu" name="data_startu_cyklu" required>
    </div>

    <div class="form-group">
        <label for="data_konca_cyklu">Zajęcia do dnia</label>
        <input type="date" id="data_konca_cyklu" name="data_konca_cyklu" required>
    </div>

    <button type="submit">Zapisz Szablon i Generuj Terminy</button>
</form>