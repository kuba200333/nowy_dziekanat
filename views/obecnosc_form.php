<?php
// Plik: views/obecnosc_form.php (Wersja z poprawnymi wartościami)

$termin_id = (int)$_GET['termin_id'];
// Pobranie info o zajęciach
$info_sql = "SELECT p.nazwa_przedmiotu, z.forma_zajec, t.data_zajec FROM TerminyZajec t JOIN Zajecia z ON t.zajecia_id=z.zajecia_id JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id=k.konfiguracja_id JOIN Przedmioty p ON k.przedmiot_id=p.przedmiot_id WHERE t.termin_id = ?";
$stmt_info = $conn->prepare($info_sql);
$stmt_info->bind_param("i", $termin_id);
$stmt_info->execute();
$info = $stmt_info->get_result()->fetch_assoc();

// Pobranie studentów i ich aktualnego statusu obecności
$studenci_sql = "
    SELECT s.numer_albumu, s.imie, s.nazwisko, o.status_obecnosci 
    FROM ZapisyStudentow zs
    JOIN Studenci s ON zs.numer_albumu = s.numer_albumu
    LEFT JOIN Obecnosc o ON zs.numer_albumu = o.numer_albumu AND o.termin_id = ?
    WHERE zs.zajecia_id = (SELECT zajecia_id FROM TerminyZajec WHERE termin_id = ?)
    ORDER BY s.nazwisko
";
$stmt_studenci = $conn->prepare($studenci_sql);
$stmt_studenci->bind_param("ii", $termin_id, $termin_id);
$stmt_studenci->execute();
$studenci = $stmt_studenci->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h1>Sprawdzanie Obecności</h1>
<h3><?= htmlspecialchars($info['nazwa_przedmiotu'] . ' - ' . $info['forma_zajec']) ?></h3>
<h4>Data: <?= htmlspecialchars($info['data_zajec']) ?></h4>

<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="zapisz_obecnosc">
    <input type="hidden" name="termin_id" value="<?= $termin_id ?>">
    <table>
        <thead><tr><th>Student</th><th>Status Obecności</th></tr></thead>
        <tbody>
            <?php foreach($studenci as $student): ?>
            <tr>
                <td><?= htmlspecialchars($student['imie'] . ' ' . $student['nazwisko']) ?></td>
                <td>
                    <select name="obecnosc[<?= $student['numer_albumu'] ?>]">
                        <option value="Obecny" <?= ($student['status_obecnosci'] == 'Obecny') ? 'selected' : '' ?>>Obecny</option>
                        <option value="Nieobecny" <?= ($student['status_obecnosci'] == 'Nieobecny') ? 'selected' : '' ?>>Nieobecny</option>
                        <option value="Spóźniony" <?= ($student['status_obecnosci'] == 'Spóźniony') ? 'selected' : '' ?>>Spóźniony</option>
                        <option value="Usprawiedliwiony" <?= ($student['status_obecnosci'] == 'Usprawiedliwiony') ? 'selected' : '' ?>>Usprawiedliwiony</option>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <button type="submit">Zapisz Obecność</button>
</form>