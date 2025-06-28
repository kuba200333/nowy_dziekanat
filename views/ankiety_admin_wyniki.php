<?php
// Plik: views/ankiety_admin_wyniki.php
$wyniki_sql = "
    SELECT 
        pr.prowadzacy_id,
        CONCAT(pr.tytul_naukowy, ' ', pr.imie, ' ', pr.nazwisko) as prowadzacy,
        AVG(ao.ocena_przygotowanie) as srednia_przygotowanie,
        AVG(ao.ocena_sposob_oceniania) as srednia_ocenianie,
        COUNT(ao.odpowiedz_id) as ilosc_odpowiedzi
    FROM AnkietyOdpowiedzi ao
    JOIN Zajecia z ON ao.zajecia_id = z.zajecia_id
    JOIN Prowadzacy pr ON z.prowadzacy_id = pr.prowadzacy_id
    GROUP BY pr.prowadzacy_id
    ORDER BY srednia_przygotowanie DESC, srednia_ocenianie DESC
";
$wyniki = $conn->query($wyniki_sql)->fetch_all(MYSQLI_ASSOC);
?>
<h1>Wyniki Ankietyzacji</h1>
<p>Zbiorcze wyniki ocen wystawionych przez studentów dla poszczególnych prowadzących.</p>
<table>
    <thead><tr><th>Prowadzący</th><th>Śr. ocena za przygotowanie</th><th>Śr. ocena za sposób oceniania</th><th>Ilość odpowiedzi</th></tr></thead>
    <tbody>
        <?php foreach($wyniki as $wynik): ?>
        <tr>
            <td><?= htmlspecialchars($wynik['prowadzacy']) ?></td>
            <td><strong><?= number_format($wynik['srednia_przygotowanie'], 2) ?></strong> / 5.00</td>
            <td><strong><?= number_format($wynik['srednia_ocenianie'], 2) ?></strong> / 5.00</td>
            <td><?= $wynik['ilosc_odpowiedzi'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>