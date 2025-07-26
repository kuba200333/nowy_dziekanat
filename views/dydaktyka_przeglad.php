<?php
// Plik: views/dydaktyka_przeglad.php (Wersja z nowym sortowaniem i podświetlaniem błędów)

// Pobieramy dane do filtra
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);

$wybrany_rok_id = isset($_GET['rok_id']) ? (int)$_GET['rok_id'] : 0;
$czy_filtrowac = isset($_GET['filtruj']);
$zajecia_w_roku = null;
$przedmioty_bez_zajec = null;

if ($czy_filtrowac && $wybrany_rok_id > 0) {
    // 1. Główne zapytanie o wszystkie zajęcia w danym roku
    $zajecia_sql = "
        SELECT 
            p.nazwa_przedmiotu,
            z.forma_zajec,
            g.nazwa_grupy,
            g.semestr,
            IFNULL(CONCAT(pr.tytul_naukowy, ' ', pr.imie, ' ', pr.nazwisko), NULL) AS prowadzacy,
            kk.waga_oceny
        FROM Zajecia z
        JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
        JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
        JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
        LEFT JOIN Prowadzacy pr ON z.prowadzacy_id = pr.prowadzacy_id
        LEFT JOIN KonfiguracjaKomponentow kk ON z.konfiguracja_id = kk.konfiguracja_id AND z.forma_zajec = kk.forma_zajec
        WHERE g.rok_akademicki_id = ?
        ORDER BY g.semestr DESC, p.nazwa_przedmiotu, g.nazwa_grupy, z.forma_zajec
    ";
    $stmt_zajecia = $conn->prepare($zajecia_sql);
    $stmt_zajecia->bind_param("i", $wybrany_rok_id);
    $stmt_zajecia->execute();
    $zajecia_w_roku = $stmt_zajecia->get_result()->fetch_all(MYSQLI_ASSOC);

    // 2. Zapytanie o przedmioty bez zajęć (bez zmian)
    $braki_sql = "
        SELECT p.nazwa_przedmiotu FROM KonfiguracjaPrzedmiotu k
        JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
        WHERE k.rok_akademicki_id = ? 
        AND NOT EXISTS (SELECT 1 FROM Zajecia z WHERE z.konfiguracja_id = k.konfiguracja_id)
        ORDER BY p.nazwa_przedmiotu
    ";
    $stmt_braki = $conn->prepare($braki_sql);
    $stmt_braki->bind_param("i", $wybrany_rok_id);
    $stmt_braki->execute();
    $przedmioty_bez_zajec = $stmt_braki->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<h1>Przegląd Dydaktyki</h1>
<p>Wybierz rok akademicki, aby zobaczyć pełny przegląd skonfigurowanych zajęć, ich obsady i wag.</p>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="page" value="dydaktyka_przeglad">
    <div style="display: flex; gap: 20px; align-items: end; margin-bottom: 20px;">
        <div class="form-group">
            <label for="rok_id">Wybierz rok akademicki:</label>
            <select name="rok_id" id="rok_id" required>
                <option value="">-- Wybierz z listy --</option>
                <?php foreach ($roki as $rok): ?>
                    <option value="<?= $rok['rok_akademicki_id'] ?>" <?= ($wybrany_rok_id == $rok['rok_akademicki_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rok['nazwa_roku']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="filtruj" value="1">
        <div class="form-group">
            <button type="submit" class="btn-add">Pokaż przegląd</button>
        </div>
    </div>
</form>

<?php if ($czy_filtrowac): ?>
    <table>
        <thead>
            <tr>
                <th>Przedmiot</th>
                <th>Forma</th>
                <th>Grupa</th>
                <th>Semestr</th>
                <th>Prowadzący</th>
                <th>Waga Oceny</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($zajecia_w_roku && count($zajecia_w_roku) > 0): ?>
                <?php foreach($zajecia_w_roku as $zajecia): ?>
                    <?php
                        // ZMIANA: Sprawdzamy, czy w wierszu są braki danych
                        $has_error = ($zajecia['prowadzacy'] === null || $zajecia['waga_oceny'] === null);
                    ?>
                    <tr <?= $has_error ? 'style="background-color: #fffbe6;"' : '' ?>>
                        <td><?= htmlspecialchars($zajecia['nazwa_przedmiotu']) ?></td>
                        <td><?= htmlspecialchars($zajecia['forma_zajec']) ?></td>
                        <td><?= htmlspecialchars($zajecia['nazwa_grupy']) ?></td>
                        <td><?= htmlspecialchars($zajecia['semestr']) ?></td>
                        <td>
                            <?php 
                                // Jeśli prowadzący istnieje, zabezpieczamy go. Jeśli nie, wyświetlamy nasz HTML.
                                echo $zajecia['prowadzacy'] ? htmlspecialchars($zajecia['prowadzacy']) : '<span style="color:red; font-weight:bold;">Brak</span>';
                            ?>
                        </td>
                        <td>
                            <?php
                                // Jeśli waga istnieje, formatujemy ją i zabezpieczamy. Jeśli nie, wyświetlamy nasz HTML.
                                echo $zajecia['waga_oceny'] !== null ? htmlspecialchars(number_format($zajecia['waga_oceny'], 2)) : '<span style="color:red; font-weight:bold;">Brak</span>';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Brak utworzonych zajęć dla wybranego roku akademickiego.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($przedmioty_bez_zajec && count($przedmioty_bez_zajec) > 0): ?>
        <div style="margin-top: 20px; padding: 15px; border: 1px solid #ffc107; background-color: #fffbe6; border-radius: 5px;">
            <h4>Uwagi:</h4>
            <p>Następujące przedmioty zostały skonfigurowane (mają przypisane punkty ECTS) w tym roku akademickim, ale nie mają jeszcze utworzonych żadnych zajęć (grup):</p>
            <ul>
                <?php foreach ($przedmioty_bez_zajec as $przedmiot): ?>
                    <li><?= htmlspecialchars($przedmiot['nazwa_przedmiotu']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

<?php else: ?>
    <p style="text-align:center; margin-top: 20px;">Wybierz rok akademicki i kliknij "Pokaż przegląd", aby zobaczyć dane.</p>
<?php endif; ?>