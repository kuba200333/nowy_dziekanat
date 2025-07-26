<?php
$sql = "SELECT w.*, (SELECT COUNT(*) FROM Przedmioty p WHERE p.wydzial_id = w.wydzial_id) as uzycie_count FROM Wydzialy w ORDER BY w.nazwa_wydzialu";
$result = $conn->query($sql);
?>
<h1>Lista Wydziałów</h1>
<a href="index.php?page=wydzialy_form" class="btn-add">Dodaj nowy wydział</a>
<table>
    <thead><tr><th>Nazwa Wydziału</th><th>Skrót</th><th style="width: 180px;">Akcje</th></tr></thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nazwa_wydzialu']) ?></td>
                <td><?= htmlspecialchars($row['skrot_wydzialu']) ?></td>
                <td style="display: flex; gap: 10px; align-items: center;">
                    <a href="index.php?page=wydzialy_edit_form&wydzial_id=<?= $row['wydzial_id'] ?>" class="btn-add" style="background-color: #ffc107; padding: 5px 10px; margin: 0;">Edytuj</a>
                    <form action="handler.php" method="POST" style="margin: 0;" onsubmit="return confirm('Czy na pewno chcesz usunąć ten wydział?');">
                        <input type="hidden" name="action" value="delete_wydzial"><input type="hidden" name="wydzial_id" value="<?= $row['wydzial_id'] ?>">
                        <button type="submit" class="btn-add" style="background-color: #dc3545; padding: 5px 10px; margin: 0;" <?php if ($row['uzycie_count'] > 0) echo 'disabled title="Nie można usunąć, wydział ma przypisane przedmioty"'; ?>>Usuń</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>