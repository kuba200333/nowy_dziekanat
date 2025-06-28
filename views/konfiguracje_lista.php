<?php
// Plik: views/konfiguracje_lista.php
$sql = "SELECT k.konfiguracja_id, p.nazwa_przedmiotu, r.nazwa_roku, k.punkty_ects
        FROM KonfiguracjaPrzedmiotu k
        JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
        JOIN RokiAkademickie r ON k.rok_akademicki_id = r.rok_akademicki_id
        ORDER BY p.nazwa_przedmiotu, r.nazwa_roku";
$result = $conn->query($sql);
?>
<h1>Skonfigurowane Przedmioty</h1>
<a href="index.php?page=konfiguracje_form" class="btn-add">Skonfiguruj nowy przedmiot</a>
<table>
    <thead>
        <tr>
            <th>Przedmiot</th>
            <th>Rok Akademicki</th>
            <th>Punkty ECTS</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nazwa_przedmiotu']) ?></td>
                    <td><?= htmlspecialchars($row['nazwa_roku']) ?></td>
                    <td><?= htmlspecialchars($row['punkty_ects']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3">Brak skonfigurowanych przedmiotów.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>