<?php
// Plik: views/sale_lista.php (Wersja z edycją i usuwaniem)
$sql = "
    SELECT 
        s.sala_id, s.budynek, s.numer_sali,
        (SELECT COUNT(*) FROM TerminyZajec t WHERE t.sala_id = s.sala_id) AS uzycie_count
    FROM SaleZajeciowe s 
    ORDER BY s.budynek, s.numer_sali
";
$result = $conn->query($sql);
?>
<h1>Lista Sal Zajęciowych</h1>
<a href="index.php?page=sale_form" class="btn-add">Dodaj nową salę</a>
<table>
    <thead>
        <tr>
            <th>Budynek</th>
            <th>Numer Sali</th>
            <th style="width: 180px;">Akcje</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['budynek']) ?></td>
                    <td><?= htmlspecialchars($row['numer_sali']) ?></td>
                    <td style="display: flex; gap: 10px; align-items: center;">
                        <a href="index.php?page=sale_edit_form&sala_id=<?= $row['sala_id'] ?>" class="btn-add" style="background-color: #ffc107; padding: 5px 10px; margin: 0;">Edytuj</a>
                        <form action="handler.php" method="POST" style="margin: 0;" onsubmit="return confirm('Czy na pewno chcesz usunąć tę salę?');">
                            <input type="hidden" name="action" value="delete_sala">
                            <input type="hidden" name="sala_id" value="<?= $row['sala_id'] ?>">
                            <button type="submit" class="btn-add" style="background-color: #dc3545; padding: 5px 10px; margin: 0;" 
                                <?php if ($row['uzycie_count'] > 0) echo 'disabled title="Nie można usunąć, sala jest używana w planie zajęć."'; ?>>
                                Usuń
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">Brak zdefiniowanych sal.</td></tr>
        <?php endif; ?>
    </tbody>
</table>