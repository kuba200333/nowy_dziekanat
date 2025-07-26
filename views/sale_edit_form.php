<?php
// Plik: views/sale_edit_form.php
if (!isset($_GET['sala_id'])) {
    echo "<h1>Błąd</h1><p>Nie podano ID sali.</p>";
    return;
}
$sala_id = (int)$_GET['sala_id'];
$stmt = $conn->prepare("SELECT * FROM SaleZajeciowe WHERE sala_id = ?");
$stmt->bind_param("i", $sala_id);
$stmt->execute();
$sala = $stmt->get_result()->fetch_assoc();
if (!$sala) {
    echo "<h1>Błąd</h1><p>Nie znaleziono sali.</p>";
    return;
}
?>
<h1>Edytuj Salę Zajęciową</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="edit_sala">
    <input type="hidden" name="sala_id" value="<?= $sala_id ?>">
    <div class="form-group"><label for="budynek">Budynek</label><input type="text" id="budynek" name="budynek" value="<?= htmlspecialchars($sala['budynek']) ?>" required></div>
    <div class="form-group"><label for="numer_sali">Numer Sali</label><input type="text" id="numer_sali" name="numer_sali" value="<?= htmlspecialchars($sala['numer_sali']) ?>" required></div>
    <button type="submit">Zapisz Zmiany</button>
</form>