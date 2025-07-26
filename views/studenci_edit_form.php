<?php
// Plik: views/studenci_edit_form.php (Wersja z wszystkimi polami)
$numer_albumu = (int)$_GET['numer_albumu'];
$stmt = $conn->prepare("SELECT * FROM Studenci WHERE numer_albumu = ?");
$stmt->bind_param("i", $numer_albumu);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
?>
<h1>Edytuj Dane Studenta</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="edit_student">
    <input type="hidden" name="numer_albumu" value="<?= $numer_albumu ?>">
    
    <h3>Dane Osobowe</h3>
    <div class="form-group"><label for="imie">Imię</label><input type="text" id="imie" name="imie" value="<?= htmlspecialchars($student['imie']) ?>" required></div>
    <div class="form-group"><label for="nazwisko">Nazwisko</label><input type="text" id="nazwisko" name="nazwisko" value="<?= htmlspecialchars($student['nazwisko']) ?>" required></div>
    <div class="form-group"><label for="pesel">PESEL</label><input type="text" id="pesel" name="pesel" value="<?= htmlspecialchars($student['pesel']) ?>"></div>
    <div class="form-group"><label for="data_urodzenia">Data urodzenia</label><input type="date" id="data_urodzenia" name="data_urodzenia" value="<?= htmlspecialchars($student['data_urodzenia']) ?>"></div>
    <div class="form-group"><label for="adres_zamieszkania">Adres zamieszkania</label><textarea id="adres_zamieszkania" name="adres_zamieszkania"><?= htmlspecialchars($student['adres_zamieszkania']) ?></textarea></div>
    <div class="form-group"><label for="telefon">Telefon</label><input type="text" id="telefon" name="telefon" value="<?= htmlspecialchars($student['telefon']) ?>"></div>
    <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required></div>

    <hr>
    <h3>Dane Akademickie</h3>
    <div class="form-group"><label for="rok_rozpoczecia_studiow">Rok Rozpoczęcia Studiów</label><input type="number" id="rok_rozpoczecia_studiow" name="rok_rozpoczecia_studiow" value="<?= htmlspecialchars($student['rok_rozpoczecia_studiow']) ?>" required></div>
    <div class="form-group">
        <label for="status_studenta">Status</label>
        <select id="status_studenta" name="status_studenta" required>
            <option value="aktywny" <?= ($student['status_studenta'] == 'aktywny') ? 'selected' : '' ?>>Aktywny</option>
            <option value="urlop" <?= ($student['status_studenta'] == 'urlop') ? 'selected' : '' ?>>Urlop</option>
            <option value="absolwent" <?= ($student['status_studenta'] == 'absolwent') ? 'selected' : '' ?>>Absolwent</option>
            <option value="skreślony" <?= ($student['status_studenta'] == 'skreślony') ? 'selected' : '' ?>>Skreślony</option>
        </select>
    </div>
    <button type="submit">Zapisz Zmiany</button>
</form>