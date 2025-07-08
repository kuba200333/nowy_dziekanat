<?php
// Plik: views/przedmioty_edit_form.php
if (!isset($_GET['przedmiot_id'])) {
    echo "<h1>Błąd</h1><p>Nie podano ID przedmiotu.</p>";
    return;
}
$przedmiot_id = (int)$_GET['przedmiot_id'];

$stmt = $conn->prepare("SELECT nazwa_przedmiotu FROM Przedmioty WHERE przedmiot_id = ?");
$stmt->bind_param("i", $przedmiot_id);
$stmt->execute();
$przedmiot = $stmt->get_result()->fetch_assoc();

if (!$przedmiot) {
    echo "<h1>Błąd</h1><p>Nie znaleziono przedmiotu.</p>";
    return;
}
?>
<h1>Edytuj Nazwę Przedmiotu</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="edit_przedmiot">
    <input type="hidden" name="przedmiot_id" value="<?= $przedmiot_id ?>">
    <div class="form-group">
        <label for="nazwa_przedmiotu">Nowa Nazwa Przedmiotu</label>
        <input type="text" id="nazwa_przedmiotu" name="nazwa_przedmiotu" value="<?= htmlspecialchars($przedmiot['nazwa_przedmiotu']) ?>" required>
    </div>
    <button type="submit">Zapisz Zmiany</button>
</form>