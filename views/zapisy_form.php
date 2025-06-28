<?php
// Plik: views/zapisy_form.php
$studenci = $conn->query("SELECT * FROM Studenci ORDER BY nazwisko, imie")->fetch_all(MYSQLI_ASSOC);
$zajecia_sql = "SELECT z.zajecia_id, p.nazwa_przedmiotu, g.nazwa_grupy, z.forma_zajec 
                FROM Zajecia z 
                JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id 
                JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id 
                JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id 
                ORDER BY p.nazwa_przedmiotu";
$zajecia = $conn->query($zajecia_sql)->fetch_all(MYSQLI_ASSOC);
?>
<h1>Zapisz Studenta na Zajęcia</h1>
<p>Ten formularz służy do elastycznego przypisywania studentów do zajęć, nawet jeśli są z innej grupy.</p>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="add_zapis">
    <div class="form-group">
        <label for="z_student_id">Student</label>
        <select id="z_student_id" name="numer_albumu" required>
            <option value="">-- Wybierz studenta --</option>
            <?php foreach ($studenci as $student): ?>
                <option value="<?= $student['numer_albumu'] ?>"><?= htmlspecialchars($student['nazwisko'] . ' ' . $student['imie'] . ' (' . $student['numer_albumu'] . ')') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="z_zajecia_id">Zajęcia</label>
         <select id="z_zajecia_id" name="zajecia_id" required>
            <option value="">-- Wybierz zajęcia --</option>
            <?php foreach ($zajecia as $z): ?>
                <option value="<?= $z['zajecia_id'] ?>"><?= htmlspecialchars($z['nazwa_przedmiotu'] . ' - ' . $z['forma_zajec'] . ' (' . $z['nazwa_grupy'] . ')') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit">Zapisz Studenta</button>
</form>