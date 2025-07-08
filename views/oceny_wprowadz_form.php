<?php
// Plik: views/oceny_wprowadz_form.php (Wersja z terminami w kolumnach)

if (!isset($_GET['zajecia_id'])) {
    echo "<h1>Błąd</h1><p>Nie wybrano zajęć. <a href='index.php?page=oceny_wybor_krok1'>Wróć do wyboru</a>.</p>";
    return;
}

$zajecia_id = (int)$_GET['zajecia_id'];

// Pobranie info o zajęciach do nagłówka
$info_sql = "SELECT p.nazwa_przedmiotu, z.forma_zajec, g.nazwa_grupy FROM Zajecia z JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id WHERE z.zajecia_id = ?";
$stmt_info = $conn->prepare($info_sql);
$stmt_info->bind_param("i", $zajecia_id);
$stmt_info->execute();
$info = $stmt_info->get_result()->fetch_assoc();
$naglowek = htmlspecialchars($info['nazwa_przedmiotu'] . ' - ' . $info['forma_zajec'] . ' (' . $info['nazwa_grupy'] . ')');

// Pobranie studentów zapisanych na te zajęcia
$studenci_sql = "SELECT s.numer_albumu, s.imie, s.nazwisko, zs.zapis_id FROM ZapisyStudentow zs JOIN Studenci s ON zs.numer_albumu = s.numer_albumu WHERE zs.zajecia_id = ? ORDER BY s.nazwisko, s.imie";
$stmt_studenci = $conn->prepare($studenci_sql);
$stmt_studenci->bind_param("i", $zajecia_id);
$stmt_studenci->execute();
$studenci = $stmt_studenci->get_result()->fetch_all(MYSQLI_ASSOC);

// Pobranie istniejących ocen z komponentu z poprawnej tabeli
$oceny_sql = "SELECT zapis_id, termin, wartosc_oceny, czy_zatwierdzona FROM OcenyKoncoweZKomponentu WHERE zapis_id IN (SELECT zapis_id FROM ZapisyStudentow WHERE zajecia_id = ?)";
$stmt_oceny = $conn->prepare($oceny_sql);
$stmt_oceny->bind_param("i", $zajecia_id);
$stmt_oceny->execute();
$oceny_result = $stmt_oceny->get_result()->fetch_all(MYSQLI_ASSOC);
$istniejace_oceny = [];
foreach ($oceny_result as $ocena) {
    $istniejace_oceny[$ocena['zapis_id']][$ocena['termin']] = ['wartosc' => $ocena['wartosc_oceny'], 'zatwierdzona' => $ocena['czy_zatwierdzona']];
}
?>

<h1>Wystawianie Oceny Końcowej z Komponentu: <?= $naglowek ?></h1>

<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="wystaw_ocene_z_komponentu">
    <input type="hidden" name="zajecia_id" value="<?= $zajecia_id ?>">
    
    <table>
        <thead>
            <tr>
                <th rowspan="2">Student</th>
                <th colspan="2">Termin 1</th>
                <th colspan="2">Termin 2 (popr. I)</th>
                <th colspan="2">Termin 3 (popr. II)</th>
            </tr>
            <tr>
                <th>Ocena</th>
                <th>Zatwierdź</th>
                <th>Ocena</th>
                <th>Zatwierdź</th>
                <th>Ocena</th>
                <th>Zatwierdź</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($studenci) > 0): ?>
                <?php foreach ($studenci as $student): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($student['imie'] . ' ' . $student['nazwisko']) ?><br>
                            <small>(<?= htmlspecialchars($student['numer_albumu']) ?>)</small>
                        </td>
                        <?php for ($termin = 1; $termin <= 3; $termin++): ?>
                            <?php
                                $ocena_dane = $istniejace_oceny[$student['zapis_id']][$termin] ?? null;
                                $wartosc = $ocena_dane['wartosc'] ?? '';
                                $zatwierdzona = $ocena_dane['zatwierdzona'] ?? false;
                                $disabled = $zatwierdzona ? 'disabled' : '';
                            ?>
                            <td>
                                <select name="oceny[<?= $student['zapis_id'] ?>][<?= $termin ?>]" <?= $disabled ?>>
                                    <option value="" <?= ($wartosc == '') ? 'selected' : '' ?>>Brak</option>
                                    <option value="2.0" <?= ($wartosc == '2.00') ? 'selected' : '' ?>>2.0</option>
                                    <option value="3.0" <?= ($wartosc == '3.00') ? 'selected' : '' ?>>3.0</option>
                                    <option value="3.5" <?= ($wartosc == '3.50') ? 'selected' : '' ?>>3.5</option>
                                    <option value="4.0" <?= ($wartosc == '4.00') ? 'selected' : '' ?>>4.0</option>
                                    <option value="4.5" <?= ($wartosc == '4.50') ? 'selected' : '' ?>>4.5</option>
                                    <option value="5.0" <?= ($wartosc == '5.00') ? 'selected' : '' ?>>5.0</option>
                                    <option value="zal" <?= (strtolower($wartosc) == 'zal') ? 'selected' : '' ?>>zal</option>
                                    <option value="nzal" <?= (strtolower($wartosc) == 'nzal') ? 'selected' : '' ?>>nzal</option>
                                </select>
                            </td>
                            <td style="text-align: center;">
                                <input type="checkbox" name="zatwierdzone[<?= $student['zapis_id'] ?>][<?= $termin ?>]" value="1" <?= $zatwierdzona ? 'checked' : '' ?> <?= $disabled ?>>
                                <?php if ($zatwierdzona) echo "✔️"; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">Brak studentów zapisanych na te zajęcia.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if (count($studenci) > 0): ?>
        <br><button type="submit">Zapisz Zmiany</button>
    <?php endif; ?>
</form>