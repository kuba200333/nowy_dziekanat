<?php
// Plik: views/zapisy_masowe_krok1.php

$zajecia_sql = "
    SELECT z.zajecia_id, p.nazwa_przedmiotu, z.forma_zajec, g.nazwa_grupy, g.semestr, ra.nazwa_roku
    FROM Zajecia z
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    JOIN rokiakademickie ra on g.rok_akademicki_id= ra.rok_akademicki_id
    ORDER BY p.nazwa_przedmiotu, g.nazwa_grupy, z.forma_zajec, ra.nazwa_roku, g.semestr";
$zajecia_lista = $conn->query($zajecia_sql)->fetch_all(MYSQLI_ASSOC);
?>

<h1>Zarządzanie Masowe Zapisami (Krok 1/3)</h1>
<p>Wybierz zajęcia, na które chcesz masowo zapisać lub wypisać studentów.</p>

<form action="index.php" method="GET">
    <input type="hidden" name="page" value="zapisy_masowe_krok2">
    
    <div class="form-group">
        <label for="zajecia_id">Wybierz zajęcia (Przedmiot - Forma - Grupa)</label>
        <select id="zajecia_id" name="zajecia_id" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach ($zajecia_lista as $zajecia): ?>
                <option value="<?= $zajecia['zajecia_id'] ?>">
                    <?php
                        echo htmlspecialchars(
                            $zajecia['nazwa_przedmiotu'] . ' - ' . 
                            $zajecia['forma_zajec'] . ' (' . 
                            'Rok: ' . $zajecia['nazwa_roku'] . 
                            ', semestr: ' . $zajecia['semestr'] . 
                            ', ' . $zajecia['nazwa_grupy'] . 
                            ')'
                        );
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <button type="submit">Wybierz rocznik studentów</button>
</form>