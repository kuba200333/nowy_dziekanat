<?php
$wydzial_id = (int)$_GET['wydzial_id'];
$stmt = $conn->prepare("SELECT * FROM Wydzialy WHERE wydzial_id = ?");
$stmt->bind_param("i", $wydzial_id); $stmt->execute();
$wydzial = $stmt->get_result()->fetch_assoc();
?>
<h1>Edytuj Wydział</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="edit_wydzial">
    <input type="hidden" name="wydzial_id" value="<?= $wydzial_id ?>">
    <div class="form-group"><label for="nazwa_wydzialu">Pełna nazwa wydziału:</label><input type="text" name="nazwa_wydzialu" value="<?= htmlspecialchars($wydzial['nazwa_wydzialu']) ?>" required></div>
    <div class="form-group"><label for="skrot_wydzialu">Skrót (np. WI):</label><input type="text" name="skrot_wydzialu" value="<?= htmlspecialchars($wydzial['skrot_wydzialu']) ?>"></div>
    <button type="submit">Zapisz zmiany</button>
</form>