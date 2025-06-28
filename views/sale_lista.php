<?php
// Plik: views/sale_lista.php
$result = $conn->query("SELECT * FROM SaleZajeciowe ORDER BY budynek, numer_sali");
?>
<h1>Sale Zajęciowe</h1>
<a href="index.php?page=sale_form" class="btn-add">Dodaj nową salę</a>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Budynek</th>
            <th>Numer Sali</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['sala_id']) ?></td>
                    <td><?= htmlspecialchars($row['budynek']) ?></td>
                    <td><?= htmlspecialchars($row['numer_sali']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3">Brak zdefiniowanych sal.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>