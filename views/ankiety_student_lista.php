<?php
// Plik: views/ankiety_student_lista.php
$numer_albumu = $_SESSION['user_id'];

// Znajdź aktywny okres ankietyzacji
$okres = $conn->query("SELECT * FROM AnkietyOkresy WHERE status = 'otwarta' AND NOW() BETWEEN data_startu AND data_konca LIMIT 1")->fetch_assoc();

$zajecia_do_oceny = [];
if ($okres) {
    // Pobierz zajęcia studenta z semestru objętego ankietą
    $zajecia_sql = "
        SELECT z.zajecia_id, p.nazwa_przedmiotu, CONCAT(pr.tytul_naukowy, ' ', pr.imie, ' ', pr.nazwisko) as prowadzacy
        FROM ZapisyStudentow zs
        JOIN Zajecia z ON zs.zajecia_id = z.zajecia_id
        JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
        JOIN Przedmioty p ON (SELECT k.przedmiot_id FROM KonfiguracjaPrzedmiotu k WHERE k.konfiguracja_id = z.konfiguracja_id) = p.przedmiot_id
        JOIN Prowadzacy pr ON z.prowadzacy_id = pr.prowadzacy_id
        WHERE zs.numer_albumu = ? AND g.semestr = ?
    ";
    $stmt = $conn->prepare($zajecia_sql);
    $stmt->bind_param("ii", $numer_albumu, $okres['semestr']);
    $stmt->execute();
    $zajecia_do_oceny = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Sprawdź, które zajęcia student już ocenił
    $ocenione_sql = "SELECT zajecia_id FROM AnkietyOdpowiedzi WHERE numer_albumu = ? AND okres_id = ?";
    $stmt_ocenione = $conn->prepare($ocenione_sql);
    $stmt_ocenione->bind_param("ii", $numer_albumu, $okres['okres_id']);
    $stmt_ocenione->execute();
    $ocenione_res = $stmt_ocenione->get_result()->fetch_all(MYSQLI_ASSOC);
    $ocenione_ids = array_column($ocenione_res, 'zajecia_id');
}
?>

<h1>Ankietyzacja: <?= htmlspecialchars($okres['nazwa_ankiety'] ?? 'Brak aktywnej ankiety') ?></h1>

<?php if ($okres): ?>
<table>
    <thead><tr><th>Przedmiot</th><th>Prowadzący</th><th>Status / Akcja</th></tr></thead>
    <tbody>
        <?php foreach($zajecia_do_oceny as $zajecia): ?>
        <tr>
            <td><?= htmlspecialchars($zajecia['nazwa_przedmiotu']) ?></td>
            <td><?= htmlspecialchars($zajecia['prowadzacy']) ?></td>
            <td>
                <?php if(in_array($zajecia['zajecia_id'], $ocenione_ids)): ?>
                    <span style="color: green; font-weight: bold;">Oceniono</span>
                <?php else: ?>
                    <a href="index.php?page=ankiety_student_form&okres_id=<?= $okres['okres_id'] ?>&zajecia_id=<?= $zajecia['zajecia_id'] ?>" class="btn-add">Oceń</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p>Obecnie nie trwa żadna ankietyzacja.</p>
<?php endif; ?>