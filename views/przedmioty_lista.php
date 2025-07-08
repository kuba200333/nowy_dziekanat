<?php
// Plik: views/przedmioty_lista.php (Wersja z poprawionym wyglądem przycisków)

$sql = "
    SELECT 
        p.przedmiot_id, 
        p.nazwa_przedmiotu,
        (SELECT COUNT(*) FROM KonfiguracjaPrzedmiotu k WHERE k.przedmiot_id = p.przedmiot_id) AS uzycie_count
    FROM Przedmioty p 
    ORDER BY p.nazwa_przedmiotu
";
$result = $conn->query($sql);
?>
<h1>Lista Przedmiotów</h1>
<a href="index.php?page=przedmioty_form" class="btn-add">Dodaj nowy przedmiot</a>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nazwa Przedmiotu</th>
            <th style="width: 180px;">Akcje</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['przedmiot_id']) ?></td>
                    <td><?= htmlspecialchars($row['nazwa_przedmiotu']) ?></td>
                    <td style="display: flex; gap: 10px; align-items: center;">
                        <a href="index.php?page=przedmioty_edit_form&przedmiot_id=<?= $row['przedmiot_id'] ?>" class="btn-add" style="background-color: #ffc107; padding: 5px 10px; margin: 0;">Edytuj</a>
                        
                        <form action="handler.php" method="POST" style="margin: 0;" onsubmit="return confirm('Czy na pewno chcesz usunąć ten przedmiot?');">
                            <input type="hidden" name="action" value="delete_przedmiot">
                            <input type="hidden" name="przedmiot_id" value="<?= $row['przedmiot_id'] ?>">
                            <button type="submit" class="btn-add" style="background-color: #dc3545; padding: 5px 10px; margin: 0;" 
                                <?php if ($row['uzycie_count'] > 0) echo 'disabled title="Nie można usunąć, przedmiot jest w użyciu"'; ?>>
                                Usuń
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3">Brak przedmiotów w bazie danych.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>