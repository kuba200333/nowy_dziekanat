<?php
// Plik: views/student_profil.php
if (!isset($_GET['numer_albumu'])) { return; }
$numer_albumu = (int)$_GET['numer_albumu'];
$student_info = $conn->query("SELECT * FROM Studenci WHERE numer_albumu = $numer_albumu")->fetch_assoc();
$tab = $_GET['tab'] ?? 'dane'; // Domyślna zakładka
?>
<h1>Profil Studenta: <?= htmlspecialchars($student_info['imie'] . ' ' . $student_info['nazwisko']) ?></h1>
<div class="tab-navigation" style="margin-bottom: 20px;">
    <a href="?page=student_profil&numer_albumu=<?= $numer_albumu ?>&tab=dane" class="btn-add <?= $tab=='dane' ? 'active' : '' ?>">Dane studenta</a>
    <a href="?page=student_profil&numer_albumu=<?= $numer_albumu ?>&tab=przebieg" class="btn-add <?= $tab=='przebieg' ? 'active' : '' ?>">Przebieg studiów</a>
    <a href="?page=student_profil&numer_albumu=<?= $numer_albumu ?>&tab=oceny" class="btn-add <?= $tab=='oceny' ? 'active' : '' ?>">Oceny</a>
</div>

<div class="tab-content">
<?php
    // Dołączamy zawartość odpowiedniej zakładki
    if ($tab == 'dane') {
        include 'student_profil_dane.php';
    } elseif ($tab == 'przebieg') {
        include 'student_profil_przebieg.php';
    } elseif ($tab == 'oceny') {
        // Aby dołączyć widok ocen, symulujemy zmienne, których oczekuje
        $_GET['semestr'] = $_GET['semestr'] ?? 1; // Przykładowy domyślny semestr
        include 'karta_studenta_pokaz.php';
    }
?>
</div>