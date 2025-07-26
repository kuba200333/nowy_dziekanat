<?php
// Plik: views/konfiguracje_komponentow_form.php (Wersja z filtrowaniem po roku)

// Pobieramy dane do filtra
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);

// Pobieramy wybrany rok z adresu URL
$wybrany_rok_id = isset($_GET['rok_id']) ? (int)$_GET['rok_id'] : 0;

$konfiguracje = [];
// Pobieramy konfiguracje tylko jeśli rok został wybrany
if ($wybrany_rok_id > 0) {
    $konfiguracje = $conn->query("
        SELECT k.konfiguracja_id, p.nazwa_przedmiotu, r.nazwa_roku 
        FROM KonfiguracjaPrzedmiotu k 
        JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id 
        JOIN RokiAkademickie r ON k.rok_akademicki_id = r.rok_akademicki_id 
        WHERE k.rok_akademicki_id = $wybrany_rok_id
        ORDER BY p.nazwa_przedmiotu
    ")->fetch_all(MYSQLI_ASSOC);
}
?>
<h1>Zarządzanie Wagami Komponentów</h1>
<p>W tym miejscu zdefiniuj wagi dla poszczególnych form zajęć w ramach przedmiotu na dany rok akademicki.</p>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="page" value="konfiguracje_komponentow_form">
    <div style="display: flex; gap: 20px; align-items: end; margin-bottom: 20px;">
        <div class="form-group">
            <label for="rok_id">Krok 1: Wybierz rok akademicki:</label>
            <select name="rok_id" id="rok_id" onchange="this.form.submit()" required>
                <option value="">-- Wybierz z listy --</option>
                <?php foreach ($roki as $rok): ?>
                    <option value="<?= $rok['rok_akademicki_id'] ?>" <?= ($wybrany_rok_id == $rok['rok_akademicki_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rok['nazwa_roku']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>


<?php if ($wybrany_rok_id > 0): // Główny formularz pokazuje się tylko po wybraniu roku ?>
    <hr>
    <h3>Krok 2: Ustaw wagę dla komponentu</h3>
    <form action="handler.php" method="POST">
        <input type="hidden" name="action" value="zapisz_konfiguracje_komponentu">
        
        <div class="form-group">
            <label for="konfiguracja_id">Wybierz przedmiot:</label>
            <select id="konfiguracja_id" name="konfiguracja_id" required>
                <option value="">-- Wybierz z listy --</option>
                <?php foreach ($konfiguracje as $konf): ?>
                    <option value="<?= $konf['konfiguracja_id'] ?>"><?= htmlspecialchars($konf['nazwa_przedmiotu']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="forma_zajec">Forma Zajęć</label>
            <select id="forma_zajec" name="forma_zajec" required>
                <option value="Wykład">Wykład</option>
                <option value="Laboratorium">Laboratorium</option>
                <option value="Audytoryjne">Audytoryjne</option>
                <option value="Projekt">Projekt</option>
                <option value="Seminarium">Seminarium</option>
                <option value="Lektorat">Lektorat</option>
            </select>
        </div>
        <div class="form-group">
            <label for="waga_oceny">Waga Oceny (np. 0.40)</label>
            <input type="number" step="0.01" min="0" max="1" id="waga_oceny" name="waga_oceny" required>
        </div>
        <button type="submit">Zapisz Wagę</button>
    </form>
<?php endif; ?>