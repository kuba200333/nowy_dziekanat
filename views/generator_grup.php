<?php
// Plik: views/generator_grup.php
$wybory = $conn->query("SELECT * FROM WyboryPrzedmiotow ORDER BY data_zakonczenia DESC")->fetch_all(MYSQLI_ASSOC);

// Sprawdzamy, czy mamy wyświetlić wcześniej wygenerowane grupy
$wybrany_wybor_id = $_GET['wybor_id'] ?? 0;
$wygenerowane_grupy = [];
if ($wybrany_wybor_id > 0) {
    $sql = "
        SELECT 
            gr.grupa_robocza_id, gr.nazwa_grupy_roboczej, gr.opis_opcji_wyboru, 
            GROUP_CONCAT(CONCAT(s.imie, ' ', s.nazwisko, ' (', s.numer_albumu, ')') SEPARATOR '<br>') as czlonkowie
        FROM GrupyRobocze gr
        LEFT JOIN CzlonkowieGrupRoboczych cgr ON gr.grupa_robocza_id = cgr.grupa_robocza_id
        LEFT JOIN Studenci s ON cgr.numer_albumu = s.numer_albumu
        WHERE gr.wybor_id = ?
        GROUP BY gr.grupa_robocza_id
        ORDER BY gr.nazwa_grupy_roboczej
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $wybrany_wybor_id);
    $stmt->execute();
    $wygenerowane_grupy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<h1>Generator Grup Dziekańskich (Brudnopis)</h1>
<p>Narzędzie tworzy roboczy podział na grupy na podstawie odpowiedzi studentów z modułu "Wybory Przedmiotów".</p>

<form action="handler.php" method="POST" style="padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
    <h3>Krok 1: Ustaw parametry generowania</h3>
    <input type="hidden" name="action" value="generuj_grupy_robocze">
    <div class="form-group">
        <label for="wybor_id">Wybierz nabór, na podstawie którego chcesz utworzyć grupy:</label>
        <select name="wybor_id" required>
            <?php foreach($wybory as $wybor): ?>
            <option value="<?= $wybor['wybor_id'] ?>"><?= htmlspecialchars($wybor['nazwa_wyboru']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="max_wielkosc_grupy">Maksymalna wielkość grupy:</label>
        <input type="number" name="max_wielkosc_grupy" min="1" value="15" required>
    </div>
    <button type="submit" class="btn-add" onclick="return confirm('Uwaga! Spowoduje to usunięcie poprzednio wygenerowanych grup roboczych dla tego naboru i stworzenie nowych. Kontynuować?');">Generuj Grupy</button>
</form>

<?php if (!empty($wygenerowane_grupy)): ?>
<hr>
<h3>Wyniki ostatniego generowania dla wybranego naboru:</h3>
<?php foreach($wygenerowane_grupy as $grupa): ?>
    <div style="margin-top: 20px;">
        <h4><?= htmlspecialchars($grupa['nazwa_grupy_roboczej']) ?></h4>
        <p><strong>Wybrane przedmioty:</strong> <?= htmlspecialchars($grupa['opis_opcji_wyboru']) ?></p>
        <table>
            <thead><tr><th>Członkowie grupy (<?= count(explode('<br>', $grupa['czlonkowie'])) ?>)</th></tr></thead>
            <tbody>
                <tr><td><?= $grupa['czlonkowie'] ?></td></tr>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
<?php endif; ?>