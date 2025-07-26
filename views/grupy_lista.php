<?php
// Plik: views/grupy_lista.php (Wersja z filtrowaniem po roku i semestrze)

// Pobieramy dane do filtrów
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);

// Pobieramy wybrane wartości filtrów z adresu URL
$wybrany_rok_id = isset($_GET['rok_id']) ? (int)$_GET['rok_id'] : 0;
$wybrany_semestr = isset($_GET['semestr']) ? (int)$_GET['semestr'] : 0;

// Sprawdzamy, czy formularz został wysłany
$czy_filtrowac = isset($_GET['filtruj']);
$result = null;

// Wykonujemy zapytanie o grupy tylko jeśli wybrano rok ORAZ semestr i kliknięto przycisk
if ($czy_filtrowac && $wybrany_rok_id > 0 && $wybrany_semestr > 0) {
    $sql = "
        SELECT 
            g.grupa_id, 
            g.nazwa_grupy, 
            g.semestr, 
            r.nazwa_roku,
            (SELECT COUNT(*) FROM Zajecia z WHERE z.grupa_id = g.grupa_id) AS uzycie_count
        FROM GrupyZajeciowe g
        JOIN RokiAkademickie r ON g.rok_akademicki_id = r.rok_akademicki_id
        WHERE g.rok_akademicki_id = ? AND g.semestr = ?
        ORDER BY g.nazwa_grupy
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $wybrany_rok_id, $wybrany_semestr);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<h1>Grupy Zajęciowe</h1>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="page" value="grupy_lista">
    <div style="display: flex; gap: 20px; align-items: end; margin-bottom: 20px;">
        <div class="form-group">
            <label for="rok_id">Rok akademicki:</label>
            <select name="rok_id" id="rok_id" required>
                <option value="">-- Wybierz rok --</option>
                <?php foreach ($roki as $rok): ?>
                    <option value="<?= $rok['rok_akademicki_id'] ?>" <?= ($wybrany_rok_id == $rok['rok_akademicki_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rok['nazwa_roku']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="semestr">Semestr:</label>
            <select name="semestr" id="semestr" required>
                <option value="">-- Wybierz semestr --</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= ($wybrany_semestr == $i) ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <input type="hidden" name="filtruj" value="1">
        <div class="form-group">
            <button type="submit" class="btn-add">Filtruj</button>
        </div>
    </div>
</form>

<a href="index.php?page=grupy_form" class="btn-add">Dodaj nową grupę</a>

<?php if ($czy_filtrowac): // Tabela wyświetla się tylko po filtrowaniu ?>
    <table>
        <thead>
            <tr>
                <th>Nazwa Grupy</th>
                <th>Semestr</th>
                <th>Rok Akademicki</th>
                <th style="width: 180px;">Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nazwa_grupy']) ?></td>
                        <td><?= htmlspecialchars($row['semestr']) ?></td>
                        <td><?= htmlspecialchars($row['nazwa_roku']) ?></td>
                        <td style="display: flex; gap: 10px; align-items: center;">
                            <a href="index.php?page=grupy_edit_form&grupa_id=<?= $row['grupa_id'] ?>" class="btn-add" style="background-color: #ffc107; padding: 5px 10px; margin: 0;">Edytuj</a>
                            <form action="handler.php" method="POST" style="margin: 0;" onsubmit="return confirm('Czy na pewno chcesz usunąć tę grupę?');">
                                <input type="hidden" name="action" value="delete_grupa">
                                <input type="hidden" name="grupa_id" value="<?= $row['grupa_id'] ?>">
                                <button type="submit" class="btn-add" style="background-color: #dc3545; padding: 5px 10px; margin: 0;" 
                                    <?php if ($row['uzycie_count'] > 0) echo 'disabled title="Nie można usunąć, grupa jest przypisana do zajęć"'; ?>>
                                    Usuń
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">Brak zdefiniowanych grup dla wybranego roku i semestru.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align:center; margin-top: 20px;">Wybierz rok akademicki oraz semestr i kliknij "Filtruj", aby wyświetlić grupy.</p>
<?php endif; ?>