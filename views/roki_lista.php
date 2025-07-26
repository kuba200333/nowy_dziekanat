<?php
// Plik: views/roki_lista.php (Wersja z edycją i usuwaniem)
$sql = "
    SELECT 
        r.rok_akademicki_id, r.nazwa_roku,
        (SELECT COUNT(*) FROM KonfiguracjaPrzedmiotu k WHERE k.rok_akademicki_id = r.rok_akademicki_id) AS uzycie_count
    FROM RokiAkademickie r 
    ORDER BY r.nazwa_roku DESC
";
$result = $conn->query($sql);
?>
<h1>Lata Akademickie</h1>
<a href="index.php?page=roki_form" class="btn-add">Dodaj nowy rok</a>
<table>
    <thead>
        <tr>
            <th>Nazwa Roku</th>
            <th style="width: 180px;">Akcje</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nazwa_roku']) ?></td>
                    <td style="display: flex; gap: 10px; align-items: center;">
                        <a href="index.php?page=roki_edit_form&rok_id=<?= $row['rok_akademicki_id'] ?>" class="btn-add" style="background-color: #ffc107; padding: 5px 10px; margin: 0;">Edytuj</a>
                        <form action="handler.php" method="POST" style="margin: 0;" onsubmit="return confirm('Czy na pewno chcesz usunąć ten rok?');">
                            <input type="hidden" name="action" value="delete_rok">
                            <input type="hidden" name="rok_id" value="<?= $row['rok_akademicki_id'] ?>">
                            <button type="submit" class="btn-add" style="background-color: #dc3545; padding: 5px 10px; margin: 0;" 
                                <?php if ($row['uzycie_count'] > 0) echo 'disabled title="Nie można usunąć, rok jest używany w konfiguracjach przedmiotów."'; ?>>
                                Usuń
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="2">Brak zdefiniowanych lat akademickich.</td></tr>
        <?php endif; ?>
    </tbody>
</table>