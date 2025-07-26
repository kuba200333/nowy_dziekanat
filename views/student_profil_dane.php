<?php
// Ten plik jest dołączany w student_profil.php, więc ma dostęp do $conn i $student_info
$numer_albumu = $student_info['numer_albumu'];

// NOWE ZAPYTANIE: Pobieramy numer ostatniego (najwyższego) semestru z przebiegu studiów
$semestr_sql = "SELECT MAX(semestr) as aktualny_semestr FROM PrzebiegStudiow WHERE numer_albumu = ?";
$stmt = $conn->prepare($semestr_sql);
$stmt->bind_param("i", $numer_albumu);
$stmt->execute();
$semestr_info = $stmt->get_result()->fetch_assoc();
$aktualny_semestr = $semestr_info['aktualny_semestr'] ?? 'Brak wpisu';

?>
<h3>Dane Osobowe i Kontaktowe</h3>
<table class="table-details">
    <tr><th>PESEL:</th><td><?= htmlspecialchars($student_info['pesel'] ?? 'Brak danych') ?></td></tr>
    <tr><th>Data urodzenia:</th><td><?= htmlspecialchars($student_info['data_urodzenia'] ?? 'Brak danych') ?></td></tr>
    <tr><th>Adres zamieszkania:</th><td><?= htmlspecialchars($student_info['adres_zamieszkania'] ?? 'Brak danych') ?></td></tr>
    <tr><th>Telefon:</th><td><?= htmlspecialchars($student_info['telefon'] ?? 'Brak danych') ?></td></tr>
    <tr><th>Email:</th><td><?= htmlspecialchars($student_info['email'] ?? 'Brak danych') ?></td></tr>
</table>

<hr>
<h3>Dane Akademickie</h3>
<table class="table-details">
    <tr><th>Numer Albumu:</th><td><?= htmlspecialchars($student_info['numer_albumu']) ?></td></tr>
    <tr><th>Aktualny semestr:</th><td><strong><?= htmlspecialchars($aktualny_semestr) ?></strong></td></tr>
    <tr><th>Rok rozpoczęcia studiów:</th><td><?= htmlspecialchars($student_info['rok_rozpoczecia_studiow']) ?></td></tr>
    <tr><th>Status:</th><td><strong><?= htmlspecialchars($student_info['status_studenta']) ?></strong></td></tr>
</table>
<hr>
<a href="index.php?page=studenci_edit_form&numer_albumu=<?= $student_info['numer_albumu'] ?>" class="btn-add">Edytuj wszystkie dane</a>