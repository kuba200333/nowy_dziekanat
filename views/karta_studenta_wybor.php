<?php
// Plik: views/karta_studenta_wybor.php

$studenci = $conn->query("SELECT numer_albumu, imie, nazwisko FROM Studenci ORDER BY nazwisko, imie")->fetch_all(MYSQLI_ASSOC);
?>

<h1>Karta Ocen Studenta</h1>
<p>Wybierz studenta, aby zobaczyć jego pełną kartę ocen semestralnych.</p>

<form action="index.php" method="GET">
    <input type="hidden" name="page" value="karta_studenta_pokaz">
    
    <div class="form-group">
        <label for="numer_albumu">Wybierz studenta</label>
        <select id="numer_albumu" name="numer_albumu" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach ($studenci as $student): ?>
                <option value="<?= $student['numer_albumu'] ?>">
                    <?= htmlspecialchars($student['nazwisko'] . ' ' . $student['imie'] . ' (' . $student['numer_albumu'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <button type="submit">Pokaż kartę ocen</button>
</form>