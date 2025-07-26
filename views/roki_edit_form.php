<?php
// Plik: views/roki_edit_form.php
if (!isset($_GET['rok_id'])) {
    echo "<h1>Błąd</h1><p>Nie podano ID roku.</p>";
    return;
}
$rok_id = (int)$_GET['rok_id'];
$stmt = $conn->prepare("SELECT nazwa_roku FROM RokiAkademickie WHERE rok_akademicki_id = ?");
$stmt->bind_param("i", $rok_id);
$stmt->execute();
$rok = $stmt->get_result()->fetch_assoc();
if (!$rok) {
    echo "<h1>Błąd</h1><p>Nie znaleziono roku.</p>";
    return;
}
?>
<h1>Edytuj Rok Akademicki</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="edit_rok">
    <input type="hidden" name="rok_id" value="<?= $rok_id ?>">
    <div class="form-group"><label for="nazwa_roku">Nazwa Roku (np. 2024/2025)</label><input type="text" id="nazwa_roku" name="nazwa_roku" value="<?= htmlspecialchars($rok['nazwa_roku']) ?>" required></div>
    <button type="submit">Zapisz Zmiany</button>
</form>