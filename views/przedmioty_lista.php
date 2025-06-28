<?php
// Plik: views/przedmioty_lista.php
$result = $conn->query("SELECT przedmiot_id, nazwa_przedmiotu FROM Przedmioty ORDER BY nazwa_przedmiotu");
?>
<h1>Lista Przedmiotów</h1>
<a href="index.php?page=przedmioty_form" class="btn-add">Dodaj nowy przedmiot</a>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nazwa Przedmiotu</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['przedmiot_id']) ?></td>
                    <td><?= htmlspecialchars($row['nazwa_przedmiotu']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="2">Brak przedmiotów w bazie danych.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>