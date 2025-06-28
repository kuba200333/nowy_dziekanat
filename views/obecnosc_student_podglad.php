<?php
// Plik: views/obecnosc_student_podglad.php
$numer_albumu = $_SESSION['user_id'];
$obecnosci_sql = "
    SELECT p.nazwa_przedmiotu, z.forma_zajec, t.data_zajec, o.status_obecnosci 
    FROM Obecnosc o
    JOIN TerminyZajec t ON o.termin_id = t.termin_id
    JOIN Zajecia z ON t.zajecia_id = z.zajecia_id
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    WHERE o.numer_albumu = ?
    ORDER BY t.data_zajec DESC
";
$stmt = $conn->prepare($obecnosci_sql);
$stmt->bind_param("i", $numer_albumu);
$stmt->execute();
$obecnosci = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<h1>Moja Obecność</h1>
<table>
    <thead><tr><th>Data</th><th>Przedmiot</th><th>Forma Zajęć</th><th>Status</th></tr></thead>
    <tbody>
        <?php if(empty($obecnosci)): ?>
            <tr><td colspan="4">Brak wpisów o obecności.</td></tr>
        <?php else: ?>
            <?php foreach($obecnosci as $obecnosc): ?>
            <tr>
                <td><?= htmlspecialchars($obecnosc['data_zajec']) ?></td>
                <td><?= htmlspecialchars($obecnosc['nazwa_przedmiotu']) ?></td>
                <td><?= htmlspecialchars($obecnosc['forma_zajec']) ?></td>
                <td><strong><?= htmlspecialchars($obecnosc['status_obecnosci']) ?></strong></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>