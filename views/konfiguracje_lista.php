<?php
// Plik: views/konfiguracje_lista.php (Wersja z filtrowaniem tylko po roku)

// Pobieramy dane do filtra
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);

// Pobieramy ID wybranego roku z adresu URL
$wybrany_rok_id = isset($_GET['rok_id']) ? (int)$_GET['rok_id'] : 0;
// Sprawdzamy, czy formularz został wysłany
$czy_filtrowac = isset($_GET['filtruj']);
$result = null;

// Wykonujemy zapytanie o konfiguracje tylko jeśli wybrano rok i kliknięto przycisk
if ($czy_filtrowac && $wybrany_rok_id > 0) {
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
        WHERE k.rok_akademicki_id = ?
        ORDER BY p.nazwa_przedmiotu
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $wybrany_rok_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<h1>Skonfigurowane Przedmioty (ECTS)</h1>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="page" value="konfiguracje_lista">
    <div style="display: flex; gap: 20px; align-items: end; margin-bottom: 20px;">
        <div class="form-group">
            <label for="rok_id">Wybierz rok akademicki:</label>
            <select name="rok_id" id="rok_id" required>
                <option value="">-- Wybierz rok --</option>
                <?php foreach ($roki as $rok): ?>
                    <option value="<?= $rok['rok_akademicki_id'] ?>" <?= ($wybrany_rok_id == $rok['rok_akademicki_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rok['nazwa_roku']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="filtruj" value="1">
        <div class="form-group">
            <button type="submit" class="btn-add">Filtruj</button>
        </div>
    </div>
</form>

<a href="index.php?page=konfiguracje_form" class="btn-add">Skonfiguruj nowy przedmiot</a>

<?php if ($czy_filtrowac): // Tabela wyświetla się tylko po filtrowaniu ?>
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
            <?php if ($result && $result->num_rows > 0): ?>
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
                    <td colspan="4">Brak skonfigurowanych przedmiotów dla wybranego roku akademickiego.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align:center; margin-top: 20px;">Wybierz rok akademicki i kliknij "Filtruj", aby wyświetlić konfiguracje.</p>
<?php endif; ?>