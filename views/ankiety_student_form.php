<?php
// Plik: views/ankiety_student_form.php
$okres_id = (int)$_GET['okres_id'];
$zajecia_id = (int)$_GET['zajecia_id'];
// Pobierz info o ocenianych zajęciach
$info_sql = "SELECT p.nazwa_przedmiotu, CONCAT(pr.tytul_naukowy, ' ', pr.imie, ' ', pr.nazwisko) as prowadzacy FROM Zajecia z JOIN Prowadzacy pr ON z.prowadzacy_id = pr.prowadzacy_id JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id=k.konfiguracja_id JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id WHERE z.zajecia_id = ?";
$stmt = $conn->prepare($info_sql);
$stmt->bind_param("i", $zajecia_id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
?>
<h1>Ocena Prowadzącego</h1>
<h3>Przedmiot: <?= htmlspecialchars($info['nazwa_przedmiotu']) ?></h3>
<h4>Prowadzący: <?= htmlspecialchars($info['prowadzacy']) ?></h4>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="zapisz_odpowiedz_ankiety">
    <input type="hidden" name="okres_id" value="<?= $okres_id ?>">
    <input type="hidden" name="zajecia_id" value="<?= $zajecia_id ?>">

    <div class="form-group">
        <label>Przygotowanie do zajęć (2-źle, 5-świetnie):</label>
        <div>
            <?php for($i=2; $i<=5; $i++): ?>
            <input type="radio" name="ocena_przygotowanie" value="<?= $i ?>" id="p<?= $i ?>" required><label for="p<?= $i ?>"><?= $i ?></label>
            <?php endfor; ?>
        </div>
    </div>
    <div class="form-group">
        <label>Sposób oceniania (2-źle, 5-świetnie):</label>
        <div>
            <?php for($i=2; $i<=5; $i++): ?>
            <input type="radio" name="ocena_sposob_oceniania" value="<?= $i ?>" id="o<?= $i ?>" required><label for="o<?= $i ?>"><?= $i ?></label>
            <?php endfor; ?>
        </div>
    </div>
    <div class="form-group">
        <label for="ocena_opisowa">Ocena opisowa (opcjonalnie):</label>
        <textarea name="ocena_opisowa" id="ocena_opisowa" rows="5"></textarea>
    </div>
    <button type="submit">Wyślij Ocenę</button>
</form>