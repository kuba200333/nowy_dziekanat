<?php
// Plik: views/wybory_student_glosuj.php
$numer_albumu = $_SESSION['user_id'];
$wybor_id = (int)$_GET['wybor_id'];

// Sprawdź, czy student już głosował
$odpowiedz_sql = "SELECT wybrana_opcja_id FROM OdpowiedziStudentow WHERE wybor_id = ? AND numer_albumu = ?";
$stmt_odp = $conn->prepare($odpowiedz_sql);
$stmt_odp->bind_param("ii", $wybor_id, $numer_albumu);
$stmt_odp->execute();
$odpowiedz = $stmt_odp->get_result()->fetch_assoc();
$juz_zaglosowano = $odpowiedz !== null;

// Pobierz opcje do wyboru
$opcje_sql = "SELECT o.opcja_id, p.nazwa_przedmiotu FROM OpcjeWyboru o JOIN Przedmioty p ON o.przedmiot_id = p.przedmiot_id WHERE o.wybor_id = ?";
$stmt_opcje = $conn->prepare($opcje_sql);
$stmt_opcje->bind_param("i", $wybor_id);
$stmt_opcje->execute();
$opcje = $stmt_opcje->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h1>Formularz Wyboru Przedmiotu</h1>

<?php if ($juz_zaglosowano): ?>
    <div class="status-message success">
        Już dokonałeś/aś wyboru w tej ankiecie. Twój wybór został zapisany.
    </div>
<?php else: ?>
    <form action="handler.php" method="POST">
        <input type="hidden" name="action" value="zapisz_wybor_studenta">
        <input type="hidden" name="wybor_id" value="<?= $wybor_id ?>">
        
        <p>Proszę, wybierz jeden z poniższych przedmiotów:</p>
        <div class="form-group">
            <?php foreach ($opcje as $opcja): ?>
                <div style="margin-bottom: 10px;">
                    <input type="radio" name="wybrana_opcja_id" value="<?= $opcja['opcja_id'] ?>" id="opcja_<?= $opcja['opcja_id'] ?>" required>
                    <label for="opcja_<?= $opcja['opcja_id'] ?>"><?= htmlspecialchars($opcja['nazwa_przedmiotu']) ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit">Zapisz mój wybór</button>
    </form>
<?php endif; ?>
<br>
<a href="index.php?page=wybory_student_lista">Wróć do listy wyborów</a>