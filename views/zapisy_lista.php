<?php
// Plik: views/zapisy_lista.php
$sql = "
    SELECT 
        pr.nazwa_przedmiotu,
        z.forma_zajec,
        g.nazwa_grupy,
        CONCAT(p.tytul_naukowy, ' ', p.imie, ' ', p.nazwisko) AS prowadzacy,
        s.numer_albumu,
        s.imie AS imie_studenta,
        s.nazwisko AS nazwisko_studenta
    FROM ZapisyStudentow zs
    JOIN Studenci s ON zs.numer_albumu = s.numer_albumu
    JOIN Zajecia z ON zs.zajecia_id = z.zajecia_id
    JOIN Prowadzacy p ON z.prowadzacy_id = p.prowadzacy_id
    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty pr ON k.przedmiot_id = pr.przedmiot_id
    ORDER BY pr.nazwa_przedmiotu, z.forma_zajec, g.nazwa_grupy, s.nazwisko;
";
$result = $conn->query($sql);
?>
<h1>Podgląd Zapisów na Zajęcia</h1>
<p>Ta tabela pokazuje, który student jest zapisany na jakie zajęcia.</p>
<table>
    <thead>
        <tr>
            <th>Przedmiot</th>
            <th>Forma Zajęć</th>
            <th>Grupa</th>
            <th>Student</th>
            <th>Prowadzący</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nazwa_przedmiotu']) ?></td>
                    <td><?= htmlspecialchars($row['forma_zajec']) ?></td>
                    <td><?= htmlspecialchars($row['nazwa_grupy']) ?></td>
                    <td><?= htmlspecialchars($row['imie_studenta'] . ' ' . $row['nazwisko_studenta'] . ' (' . $row['numer_albumu'] . ')') ?></td>
                    <td><?= htmlspecialchars($row['prowadzacy']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">Brak studentów zapisanych na zajęcia.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>