<?php
// Plik: views/konfiguracje_edit_form.php
if (!isset($_GET['konfiguracja_id'])) {
    echo "<h1>Błąd</h1><p>Nie podano ID konfiguracji.</p>";
    return;
}
$konfiguracja_id = (int)$_GET['konfiguracja_id'];

// Pobieramy dane do wyświetlenia (nazwę przedmiotu i rok) oraz wartość ECTS do edycji
$sql = "
    SELECT k.punkty_ects, p.nazwa_przedmiotu, r.nazwa_roku
    FROM KonfiguracjaPrzedmiotu k
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN RokiAkademickie r ON k.rok_akademicki_id = r.rok_akademicki_id
    WHERE k.konfiguracja_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $konfiguracja_id);
$stmt->execute();
$konfiguracja = $stmt->get_result()->fetch_assoc();

if (!$konfiguracja) {
    echo "<h1>Błąd</h1><p>Nie znaleziono konfiguracji.</p>";
    return;
}
?>
<h1>Edytuj Konfigurację Przedmiotu</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="edit_konfiguracja">
    <input type="hidden" name="konfiguracja_id" value="<?= $konfiguracja_id ?>">

    <div class="form-group">
        <label>Przedmiot:</label>
        <input type="text" value="<?= htmlspecialchars($konfiguracja['nazwa_przedmiotu']) ?>" disabled>
    </div>
    <div class="form-group">
        <label>Rok Akademicki:</label>
        <input type="text" value="<?= htmlspecialchars($konfiguracja['nazwa_roku']) ?>" disabled>
    </div>

    <div class="form-group">
        <label for="punkty_ects">Nowa liczba punktów ECTS:</label>
        <input type="number" id="punkty_ects" name="punkty_ects" value="<?= htmlspecialchars($konfiguracja['punkty_ects']) ?>" required min="0">
    </div>

    <button type="submit">Zapisz Zmiany</button>
</form>