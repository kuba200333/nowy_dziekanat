<?php
// Plik: views/zajecia_lista.php (Wersja z poprawionym pobieraniem wagi)

// ZMIANA: Zapytanie SQL zostało zaktualizowane, aby pobierać wagę z nowej tabeli KonfiguracjaKomponentow
$sql = "
    SELECT 
        z.zajecia_id, 
        p.nazwa_przedmiotu, 
        z.forma_zajec, 
        g.nazwa_grupy,
        CONCAT(pr.tytul_naukowy, ' ', pr.imie, ' ', pr.nazwisko) AS prowadzacy, 
        kk.waga_oceny
    FROM Zajecia z
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    JOIN Prowadzacy pr ON z.prowadzacy_id = pr.prowadzacy_id
    LEFT JOIN KonfiguracjaKomponentow kk ON z.konfiguracja_id = kk.konfiguracja_id AND z.forma_zajec = kk.forma_zajec
    ORDER BY p.nazwa_przedmiotu, z.forma_zajec
";
$result = $conn->query($sql);
?>
<h1>Utworzone Zajęcia</h1>
<a href="index.php?page=zajecia_form" class="btn-add">Utwórz nowe zajęcia</a>
<table>
    <thead>
        <tr>
            <th>Przedmiot</th>
            <th>Forma</th>
            <th>Grupa</th>
            <th>Prowadzący</th>
            <th>Waga Oceny</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nazwa_przedmiotu']) ?></td>
                    <td><?= htmlspecialchars($row['forma_zajec']) ?></td>
                    <td><?= htmlspecialchars($row['nazwa_grupy']) ?></td>
                    <td><?= htmlspecialchars($row['prowadzacy']) ?></td>
                    <td><?= htmlspecialchars(number_format($row['waga_oceny'] ?? 0, 2)) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">Brak utworzonych zajęć.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>