<?php
// Plik: views/grupy_lista.php
$sql = "SELECT g.grupa_id, g.nazwa_grupy, g.semestr, r.nazwa_roku
        FROM GrupyZajeciowe g
        JOIN RokiAkademickie r ON g.rok_akademicki_id = r.rok_akademicki_id
        ORDER BY r.nazwa_roku DESC, g.nazwa_grupy";
$result = $conn->query($sql);
?>
<h1>Grupy Zajęciowe</h1>
<a href="index.php?page=grupy_form" class="btn-add">Dodaj nową grupę</a>
<table>
    <thead>
        <tr>
            <th>Nazwa Grupy</th>
            <th>Semestr</th>
            <th>Rok Akademicki</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nazwa_grupy']) ?></td>
                    <td><?= htmlspecialchars($row['semestr']) ?></td>
                    <td><?= htmlspecialchars($row['nazwa_roku']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3">Brak zdefiniowanych grup.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>