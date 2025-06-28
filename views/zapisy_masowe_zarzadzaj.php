<?php
// Plik: views/zapisy_masowe_zarzadzaj.php (Wersja z sortowaniem po przynależności do grupy)

if (!isset($_GET['zajecia_id']) || !isset($_GET['rok_rozpoczecia'])) {
    echo "<h1>Błąd</h1><p>Nie wybrano zajęć lub rocznika. <a href='index.php?page=zapisy_masowe_krok1'>Wróć do kroku 1</a>.</p>";
    return;
}

$zajecia_id = (int)$_GET['zajecia_id'];
$rok_rozpoczecia = (int)$_GET['rok_rozpoczecia'];

// KROK 1: Pobierz ID grupy dla wybranych zajęć
$grupa_info_sql = "SELECT grupa_id FROM Zajecia WHERE zajecia_id = ?";
$stmt_grupa = $conn->prepare($grupa_info_sql);
$stmt_grupa->bind_param("i", $zajecia_id);
$stmt_grupa->execute();
$grupa_info = $stmt_grupa->get_result()->fetch_assoc();
$grupa_id = $grupa_info['grupa_id'] ?? 0; // Jeśli nie znajdzie grupy, ustaw na 0

// --- NOWA, BARDZIEJ ZAAWANSOWANA KWERENDA SQL ---
$studenci_sql = "
    SELECT
        s.numer_albumu,
        s.imie,
        s.nazwisko,
        -- Kolumna sprawdzająca, czy student jest zapisany na TE konkretne zajęcia
        (SELECT zs.zapis_id FROM ZapisyStudentow zs WHERE zs.numer_albumu = s.numer_albumu AND zs.zajecia_id = ?) as zapis_id_na_te_zajecia,
        
        -- Kolumna sprawdzająca, czy student jest zapisany na JAKIEKOLWIEK zajęcia w tej grupie
        (SELECT COUNT(zs2.zapis_id)
         FROM ZapisyStudentow zs2
         JOIN Zajecia z2 ON zs2.zajecia_id = z2.zajecia_id
         WHERE zs2.numer_albumu = s.numer_albumu AND z2.grupa_id = ?) as ilosc_zapisow_w_grupie
    FROM
        Studenci s
    WHERE
        s.rok_rozpoczecia_studiow = ?
    ORDER BY
        -- Logika sortowania z 3 poziomami priorytetu:
        CASE
            -- 1. Najwyższy priorytet: już zapisani na te zajęcia
            WHEN zapis_id_na_te_zajecia IS NOT NULL THEN 1
            -- 2. Średni priorytet: zapisani na inne zajęcia w tej grupie
            WHEN ilosc_zapisow_w_grupie > 0 THEN 2
            -- 3. Najniższy priorytet: reszta studentów
            ELSE 3
        END ASC,
        s.nazwisko ASC,
        s.imie ASC
";
$stmt_studenci = $conn->prepare($studenci_sql);
// Bindowanie parametrów: zajecia_id, grupa_id, rok_rozpoczecia
$stmt_studenci->bind_param("iii", $zajecia_id, $grupa_id, $rok_rozpoczecia);
$stmt_studenci->execute();
$studenci_rocznika = $stmt_studenci->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<h1>Zarządzanie Masowe Zapisami (Krok 3/3)</h1>
<p>Zaznacz studentów, którzy mają być zapisani na te zajęcia. Odznaczenie studenta spowoduje jego wypisanie. <strong>Studenci należący do grupy są na górze listy.</strong></p>

<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="zarzadzaj_zapisami_masowymi">
    <input type="hidden" name="zajecia_id" value="<?= $zajecia_id ?>">
    <input type="hidden" name="rok_rozpoczecia" value="<?= $rok_rozpoczecia ?>">
    
    <table>
        <thead>
            <tr>
                <th>Zapisz/Wypisz</th>
                <th>Student</th>
                <th>Numer Albumu</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($studenci_rocznika) > 0): ?>
                <?php foreach ($studenci_rocznika as $student): ?>
                    <?php
                        // Logika zaznaczania checkboxa pozostaje taka sama, opiera się na zapisie na TE zajęcia
                        $checked = ($student['zapis_id_na_te_zajecia'] !== NULL) ? 'checked' : '';
                    ?>
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" name="studenci_do_zapisu[]" value="<?= $student['numer_albumu'] ?>" <?= $checked ?>>
                        </td>
                        <td><?= htmlspecialchars($student['nazwisko'] . ' ' . $student['imie']) ?></td>
                        <td><?= htmlspecialchars($student['numer_albumu']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3">Brak studentów z wybranego rocznika.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (count($studenci_rocznika) > 0): ?>
        <br><button type="submit">Zapisz zmiany w zapisach</button>
    <?php endif; ?>
</form>