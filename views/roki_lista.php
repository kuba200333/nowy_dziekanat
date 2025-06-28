<?php
// Plik: views/roki_lista.php
$result = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC");
?>
<h1>Roki Akademickie</h1>
<a href="index.php?page=roki_form" class="btn-add">Dodaj nowy rok</a>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nazwa Roku</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['rok_akademicki_id']) ?></td>
                    <td><?= htmlspecialchars($row['nazwa_roku']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="2">Brak zdefiniowanych lat akademickich.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>