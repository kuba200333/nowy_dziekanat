<?php
// Plik: views/konfiguracje_lista.php (Wersja z edycją i usuwaniem)

// Zapytanie, które sprawdza, czy dana konfiguracja jest już używana w tabeli Zajecia
$sql = "
    SELECT 
        k.konfiguracja_id, 
        p.nazwa_przedmiotu, 
        r.nazwa_roku, 
        k.punkty_ects,
        (SELECT COUNT(*) FROM Zajecia z WHERE z.konfiguracja_id = k.konfiguracja_id) AS uzycie_count
    FROM KonfiguracjaPrzedmiotu k
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN RokiAkademickie r ON k.rok_akademicki_id = r.rok_akademicki_id
    ORDER BY p.nazwa_przedmiotu, r.nazwa_roku
";
$result = $conn->query($sql);
?>
<h1>Skonfigurowane Przedmioty (ECTS)</h1>
<a href="index.php?page=konfiguracje_form" class="btn-add">Skonfiguruj nowy przedmiot</a>
<table>
    <thead>
        <tr>
            <th>Przedmiot</th>
            <th>Rok Akademicki</th>
            <th>Punkty ECTS</th>
            <th style="width: 180px;">Akcje</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nazwa_przedmiotu']) ?></td>
                    <td><?= htmlspecialchars($row['nazwa_roku']) ?></td>
                    <td><?= htmlspecialchars($row['punkty_ects']) ?></td>
                    <td style="display: flex; gap: 10px; align-items: center;">
                        <a href="index.php?page=konfiguracje_edit_form&konfiguracja_id=<?= $row['konfiguracja_id'] ?>" class="btn-add" style="background-color: #ffc107; padding: 5px 10px; margin: 0;">Edytuj</a>
                        
                        <form action="handler.php" method="POST" style="margin: 0;" onsubmit="return confirm('Czy na pewno chcesz usunąć tę konfigurację?');">
                            <input type="hidden" name="action" value="delete_konfiguracja">
                            <input type="hidden" name="konfiguracja_id" value="<?= $row['konfiguracja_id'] ?>">
                            <button type="submit" class="btn-add" style="background-color: #dc3545; padding: 5px 10px; margin: 0;" 
                                <?php if ($row['uzycie_count'] > 0) echo 'disabled title="Nie można usunąć, konfiguracja jest w użyciu"'; ?>>
                                Usuń
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4">Brak skonfigurowanych przedmiotów.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>