<?php
// Plik: views/zapisy_masowe_krok1.php (Wersja z filtrowaniem Rok/Semestr)

// Pobieramy dane do filtrów
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);

// Pobieramy wybrane wartości filtrów z adresu URL
$wybrany_rok_id = isset($_GET['rok_id']) ? (int)$_GET['rok_id'] : 0;
$wybrany_semestr = isset($_GET['semestr']) ? (int)$_GET['semestr'] : 0;

$zajecia_lista = [];
// Wykonujemy zapytanie o zajęcia tylko jeśli filtry zostały wybrane
if ($wybrany_rok_id > 0 && $wybrany_semestr > 0) {
    $zajecia_sql = "
        SELECT 
            z.zajecia_id, 
            p.nazwa_przedmiotu, 
            z.forma_zajec, 
            g.nazwa_grupy
        FROM Zajecia z
        JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
        JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
        JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
        WHERE g.rok_akademicki_id = ? AND g.semestr = ?
        ORDER BY p.nazwa_przedmiotu, g.nazwa_grupy
    ";
    
    $stmt = $conn->prepare($zajecia_sql);
    $stmt->bind_param("ii", $wybrany_rok_id, $wybrany_semestr);
    $stmt->execute();
    $zajecia_lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<h1>Zarządzanie Masowe Zapisami (Krok 1)</h1>
<p>Najpierw wybierz rok i semestr, aby wyświetlić listę dostępnych zajęć.</p>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="page" value="zapisy_masowe_krok1">
    <div style="display: flex; gap: 20px; align-items: end; margin-bottom: 20px;">
        <div class="form-group">
            <label for="rok_id">Rok akademicki:</label>
            <select name="rok_id" id="rok_id" onchange="this.form.submit()">
                <option value="0">-- Wybierz rok --</option>
                <?php foreach ($roki as $rok): ?>
                    <option value="<?= $rok['rok_akademicki_id'] ?>" <?= ($wybrany_rok_id == $rok['rok_akademicki_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rok['nazwa_roku']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="semestr">Semestr:</label>
            <select name="semestr" id="semestr" onchange="this.form.submit()">
                <option value="0">-- Wybierz semestr --</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= ($wybrany_semestr == $i) ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
</form>

<?php if ($wybrany_rok_id > 0 && $wybrany_semestr > 0): ?>
    <hr>
    <h3>Krok 2: Wybierz zajęcia do zarządzania</h3>
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="zapisy_masowe_krok2">
        
        <div class="form-group">
            <label for="zajecia_id">Wybierz zajęcia z listy:</label>
            <select id="zajecia_id" name="zajecia_id" required>
                <option value="">-- Wybierz z listy --</option>
                <?php foreach ($zajecia_lista as $zajecia): ?>
                    <option value="<?= $zajecia['zajecia_id'] ?>">
                        <?= htmlspecialchars($zajecia['nazwa_przedmiotu'] . ' - ' . $zajecia['forma_zajec'] . ' (' . $zajecia['nazwa_grupy'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit">Wybierz rocznik studentów</button>
    </form>
<?php endif; ?>