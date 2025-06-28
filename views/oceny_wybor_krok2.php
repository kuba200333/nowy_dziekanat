<?php
// Plik: views/oceny_wybor_krok2.php

if (!isset($_GET['grupa_id'])) {
    echo "<h1>Błąd</h1><p>Nie wybrano grupy. <a href='index.php?page=oceny_wybor_krok1'>Wróć do kroku 1</a>.</p>";
    return;
}
$grupa_id = (int)$_GET['grupa_id'];

// Pobierz zajęcia tylko dla wybranej grupy
$zajecia_sql = "
    SELECT z.zajecia_id, p.nazwa_przedmiotu, z.forma_zajec, 
           CONCAT(pr.imie, ' ', pr.nazwisko) AS prowadzacy
    FROM Zajecia z
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN Prowadzacy pr ON z.prowadzacy_id = pr.prowadzacy_id
    WHERE z.grupa_id = ?
    ORDER BY p.nazwa_przedmiotu, z.forma_zajec";
$stmt = $conn->prepare($zajecia_sql);
$stmt->bind_param("i", $grupa_id);
$stmt->execute();
$zajecia_lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h1>Wystawianie Ocen Końcowych z Komponentu (Krok 2/2)</h1>
<p>Wybrano grupę. Teraz wybierz przedmiot i formę zajęć, z której chcesz wystawić oceny.</p>

<form action="index.php" method="GET">
    <input type="hidden" name="page" value="oceny_wprowadz_form">
    
    <div class="form-group">
        <label for="zajecia_id">Wybierz zajęcia (Przedmiot - Forma)</label>
        <select id="zajecia_id" name="zajecia_id" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach ($zajecia_lista as $zajecia): ?>
                <option value="<?= $zajecia['zajecia_id'] ?>">
                    <?= htmlspecialchars($zajecia['nazwa_przedmiotu'] . ' - ' . $zajecia['forma_zajec'] . ' (Prow. ' . $zajecia['prowadzacy'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <button type="submit">Pokaż studentów do oceny</button>
</form>