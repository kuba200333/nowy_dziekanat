<?php
// Plik: views/przedmioty_lista.php (Wersja z filtrowaniem po wydziale)

// Pobieramy listę wydziałów do filtra
$wydzialy = $conn->query("SELECT * FROM Wydzialy ORDER BY nazwa_wydzialu")->fetch_all(MYSQLI_ASSOC);

// Pobieramy ID wybranego wydziału z adresu URL, jeśli zostało przesłane
$wybrany_wydzial_id = isset($_GET['wydzial_id']) ? (int)$_GET['wydzial_id'] : 0;
// Sprawdzamy, czy formularz został wysłany (czy istnieje parametr 'filtruj')
$czy_filtrowac = isset($_GET['filtruj']);
$result = null;

// Wykonujemy zapytanie o przedmioty tylko jeśli wybrano wydział i kliknięto przycisk
if ($czy_filtrowac && $wybrany_wydzial_id > 0) {
    $sql = "
        SELECT 
            p.przedmiot_id, 
            p.nazwa_przedmiotu,
            (SELECT COUNT(*) FROM KonfiguracjaPrzedmiotu k WHERE k.przedmiot_id = p.przedmiot_id) AS uzycie_count
        FROM Przedmioty p 
        WHERE p.wydzial_id = ?
        ORDER BY p.nazwa_przedmiotu
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $wybrany_wydzial_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<h1>Lista Przedmiotów</h1>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="page" value="przedmioty_lista">
    <div style="display: flex; gap: 20px; align-items: end; margin-bottom: 20px;">
        <div class="form-group">
            <label for="wydzial_id">Wybierz wydział:</label>
            <select name="wydzial_id" id="wydzial_id" required>
                <option value="">-- Wybierz z listy --</option>
                <?php foreach ($wydzialy as $wydzial): ?>
                    <option value="<?= $wydzial['wydzial_id'] ?>" <?= ($wybrany_wydzial_id == $wydzial['wydzial_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($wydzial['nazwa_wydzialu']) ?>
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

<a href="index.php?page=przedmioty_form" class="btn-add">Dodaj nowy przedmiot</a>

<?php if ($czy_filtrowac): // Tabela wyświetla się tylko po filtrowaniu ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nazwa Przedmiotu</th>
                <th style="width: 180px;">Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
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
                    <td colspan="3">Brak przedmiotów dla wybranego wydziału.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align:center; margin-top: 20px;">Wybierz wydział i kliknij "Filtruj", aby wyświetlić listę przypisanych do niego przedmiotów.</p>
<?php endif; ?>