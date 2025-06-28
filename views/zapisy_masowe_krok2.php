<?php
// Plik: views/zapisy_masowe_krok2.php

if (!isset($_GET['zajecia_id'])) {
    echo "<h1>Błąd</h1><p>Nie wybrano zajęć. <a href='index.php?page=zapisy_masowe_krok1'>Wróć do kroku 1</a>.</p>";
    return;
}
$zajecia_id = (int)$_GET['zajecia_id'];

// Pobierz dostępne roczniki rozpoczęcia studiów
$roczniki_sql = "SELECT DISTINCT rok_rozpoczecia_studiow FROM Studenci ORDER BY rok_rozpoczecia_studiow DESC";
$roczniki = $conn->query($roczniki_sql)->fetch_all(MYSQLI_ASSOC);
?>

<h1>Zarządzanie Masowe Zapisami (Krok 2/3)</h1>
<p>Wybrano zajęcia. Teraz wybierz rocznik studentów, którym chcesz zarządzać.</p>

<form action="index.php" method="GET">
    <input type="hidden" name="page" value="zapisy_masowe_zarzadzaj">
    <input type="hidden" name="zajecia_id" value="<?= $zajecia_id ?>">
    
    <div class="form-group">
        <label for="rok_rozpoczecia">Wybierz rok rozpoczęcia studiów</label>
        <select id="rok_rozpoczecia" name="rok_rozpoczecia" required>
            <option value="">-- Wybierz rocznik --</option>
            <?php foreach ($roczniki as $rocznik): ?>
                <option value="<?= $rocznik['rok_rozpoczecia_studiow'] ?>">
                    <?= htmlspecialchars($rocznik['rok_rozpoczecia_studiow']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <button type="submit">Pokaż listę studentów</button>
</form>