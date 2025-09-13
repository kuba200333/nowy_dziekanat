<?php
// Plik: views/plan_modyfikuj_termin.php (Wersja Ostateczna)

// Krok 1: Logika do filtrów
// Pobieramy tylko te lata, w których są jakieś zajęcia
$lata_res = $conn->query("SELECT DISTINCT YEAR(data_zajec) as rok FROM TerminyZajec ORDER BY rok DESC");
$lata = $lata_res->fetch_all(MYSQLI_ASSOC);

// Tablica z polskimi nazwami miesięcy
$miesiace = [1 => 'Styczeń', 2 => 'Luty', 3 => 'Marzec', 4 => 'Kwiecień', 5 => 'Maj', 6 => 'Czerwiec', 7 => 'Lipiec', 8 => 'Sierpień', 9 => 'Wrzesień', 10 => 'Październik', 11 => 'Listopad', 12 => 'Grudzień'];

$wybrany_rok = isset($_GET['rok']) ? (int)$_GET['rok'] : 0;
$wybrany_miesiac = isset($_GET['miesiac']) ? (int)$_GET['miesiac'] : 0;
$czy_filtrowac = isset($_GET['filtruj']);
$terminy = [];

if ($czy_filtrowac && $wybrany_rok > 0 && $wybrany_miesiac > 0) {
    $sql = "
        SELECT t.termin_id, t.data_zajec, t.godzina_rozpoczecia, p.nazwa_przedmiotu, g.nazwa_grupy
        FROM TerminyZajec t
        JOIN Zajecia z ON t.zajecia_id = z.zajecia_id
        JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
        JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
        JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
        WHERE YEAR(t.data_zajec) = ? AND MONTH(t.data_zajec) = ?
        ORDER BY t.data_zajec, t.godzina_rozpoczecia
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $wybrany_rok, $wybrany_miesiac);
    $stmt->execute();
    $terminy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Krok 2: Logika do formularza edycji
$termin_do_edycji = null;
if (isset($_GET['termin_id'])) {
    $termin_id = (int)$_GET['termin_id'];
    $termin_do_edycji = $conn->query("SELECT * FROM TerminyZajec WHERE termin_id = $termin_id")->fetch_assoc();
    $prowadzacy = $conn->query("SELECT prowadzacy_id, imie, nazwisko, tytul_naukowy FROM Prowadzacy ORDER BY nazwisko, imie")->fetch_all(MYSQLI_ASSOC);
    $sale = $conn->query("SELECT sala_id, budynek, numer_sali FROM SaleZajeciowe ORDER BY budynek, numer_sali")->fetch_all(MYSQLI_ASSOC);
}
?>

<h1>Modyfikuj Istniejący Termin w Planie</h1>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="page" value="plan_modyfikuj_termin">
    <h3>Krok 1: Znajdź termin do edycji</h3>
    <div style="display: flex; gap: 20px; align-items: end; margin-bottom: 20px;">
        <div class="form-group">
            <label for="rok">Rok kalendarzowy:</label>
            <select name="rok" required>
                <option value="">-- Wybierz rok --</option>
                <?php foreach($lata as $rok): ?>
                <option value="<?= $rok['rok'] ?>" <?= ($wybrany_rok == $rok['rok']) ? 'selected' : '' ?>><?= $rok['rok'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="miesiac">Miesiąc:</label>
            <select name="miesiac" required>
                <option value="">-- Wybierz miesiąc --</option>
                <?php foreach($miesiace as $num => $nazwa): ?>
                <option value="<?= $num ?>" <?= ($wybrany_miesiac == $num) ? 'selected' : '' ?>><?= $nazwa ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="filtruj" value="1">
        <button type="submit" class="btn-add">Filtruj</button>
    </div>
</form>

<?php if ($czy_filtrowac): ?>
<form action="index.php" method="GET">
    <input type="hidden" name="page" value="plan_modyfikuj_termin">
    <input type="hidden" name="rok" value="<?= $wybrany_rok ?>">
    <input type="hidden" name="miesiac" value="<?= $wybrany_miesiac ?>">
    <input type="hidden" name="filtruj" value="1">
    <?php if (isset($_GET['return_to'])): ?>
        <input type="hidden" name="return_to" value="<?= htmlspecialchars($_GET['return_to']) ?>">
    <?php endif; ?>
    <div class="form-group">
        <label for="termin_id">Wybierz termin z listy:</label>
        <select name="termin_id" onchange="this.form.submit()">
            <option value="">-- Wybierz z listy --</option>
            <?php foreach($terminy as $termin): ?>
                <option value="<?= $termin['termin_id'] ?>" <?= (isset($_GET['termin_id']) && $_GET['termin_id'] == $termin['termin_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($termin['data_zajec'] . ' ' . substr($termin['godzina_rozpoczecia'], 0, 5) . ' - ' . $termin['nazwa_przedmiotu'] . ' (' . $termin['nazwa_grupy'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>
<?php endif; ?>


<?php if ($termin_do_edycji): ?>
<hr>
<h3>Krok 2: Edytuj wybrane zajęcia</h3>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="modyfikuj_termin">
    <input type="hidden" name="termin_id" value="<?= $termin_do_edycji['termin_id'] ?>">
    
    <?php if (isset($_GET['return_to'])): ?>
        <input type="hidden" name="return_to" value="<?= htmlspecialchars($_GET['return_to']) ?>">
    <?php endif; ?>

    <div class="form-group">
        <label for="data_zajec">Data zajęć:</label>
        <input type="date" name="data_zajec" value="<?= htmlspecialchars($termin_do_edycji['data_zajec']) ?>" required>
    </div>
    <div class="form-group">
        <label for="godzina_rozpoczecia">Godzina rozpoczęcia:</label>
        <input type="time" name="godzina_rozpoczecia" value="<?= htmlspecialchars($termin_do_edycji['godzina_rozpoczecia']) ?>" required>
    </div>
    <div class="form-group">
        <label for="godzina_zakonczenia">Godzina zakończenia:</label>
        <input type="time" name="godzina_zakonczenia" value="<?= htmlspecialchars($termin_do_edycji['godzina_zakonczenia']) ?>" required>
    </div>
    <div class="form-group">
        <label for="prowadzacy_id">Prowadzący:</label>
        <select name="prowadzacy_id" required>
            <?php foreach($prowadzacy as $prow): ?>
            <option value="<?= $prow['prowadzacy_id'] ?>" <?= ($termin_do_edycji['prowadzacy_id'] == $prow['prowadzacy_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($prow['tytul_naukowy'] . ' ' . $prow['imie'] . ' ' . $prow['nazwisko']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="sala_id">Sala:</label>
        <select name="sala_id" required>
             <?php foreach($sale as $sala): ?>
            <option value="<?= $sala['sala_id'] ?>" <?= ($termin_do_edycji['sala_id'] == $sala['sala_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($sala['budynek'] . ' ' . $sala['numer_sali']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="status">Status:</label>
        <select name="status">
            <option value="planowe" <?= ($termin_do_edycji['status'] == 'planowe') ? 'selected' : '' ?>>Planowe</option>
            <option value="odwolane" <?= ($termin_do_edycji['status'] == 'odwolane') ? 'selected' : '' ?>>Odwołane</option>
            <option value="zastepstwo" <?= ($termin_do_edycji['status'] == 'zastepstwo') ? 'selected' : '' ?>>Zastępstwo</option>
        </select>
    </div>
    <div class="form-group">
        <label for="notatki">Notatki (opcjonalne):</label>
        <textarea name="notatki"><?= htmlspecialchars($termin_do_edycji['notatki']) ?></textarea>
    </div>
    <button type="submit" class="btn-add">Zapisz zmiany</button>
</form>
<?php endif; ?>