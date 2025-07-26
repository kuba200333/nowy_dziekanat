<?php
// Plik: views/prowadzacy_lista.php (Wersja z edycją i usuwaniem)

// Zapytanie sprawdza, czy prowadzący jest przypisany do jakichkolwiek zajęć
$sql = "
    SELECT 
        p.prowadzacy_id, p.imie, p.nazwisko, p.tytul_naukowy, p.email,
        (SELECT COUNT(*) FROM Zajecia z WHERE z.prowadzacy_id = p.prowadzacy_id) AS uzycie_count
    FROM Prowadzacy p 
    ORDER BY p.nazwisko, p.imie
";
$result = $conn->query($sql);
?>
<h1>Lista Prowadzących</h1>
<a href="index.php?page=prowadzacy_form" class="btn-add">Dodaj nowego prowadzącego</a>
<table>
    <thead>
        <tr>
            <th>Tytuł, Imię i Nazwisko</th>
            <th>Email</th>
            <th style="width: 180px;">Akcje</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['tytul_naukowy'] . ' ' . $row['imie'] . ' ' . $row['nazwisko']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td style="display: flex; gap: 10px; align-items: center;">
                        <a href="index.php?page=prowadzacy_edit_form&prowadzacy_id=<?= $row['prowadzacy_id'] ?>" class="btn-add" style="background-color: #ffc107; padding: 5px 10px; margin: 0;">Edytuj</a>
                        
                        <form action="handler.php" method="POST" style="margin: 0;" onsubmit="return confirm('Czy na pewno chcesz usunąć tego prowadzącego?');">
                            <input type="hidden" name="action" value="delete_prowadzacy">
                            <input type="hidden" name="prowadzacy_id" value="<?= $row['prowadzacy_id'] ?>">
                            <button type="submit" class="btn-add" style="background-color: #dc3545; padding: 5px 10px; margin: 0;" 
                                <?php if ($row['uzycie_count'] > 0) echo 'disabled title="Nie można usunąć, prowadzący jest przypisany do zajęć."'; ?>>
                                Usuń
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3">Brak prowadzących w bazie danych.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>