<?php
// Plik: views/wybory_student_lista.php (Wersja z poprawioną logiką)
$numer_albumu = $_SESSION['user_id'];

// NOWE, POPRAWIONE ZAPYTANIE
// Pobiera aktywne wydarzenia wyboru, w których może wziąć udział zalogowany student.
// Sprawdza, czy student jest zapisany na jakiekolwiek zajęcia w grupie docelowej danego wyboru.
$wybory_sql = "
    SELECT DISTINCT w.* FROM WyboryPrzedmiotow w
    JOIN GrupyDoceloweWyboru gdw ON w.wybor_id = gdw.wybor_id
    JOIN Zajecia z ON gdw.grupa_id = z.grupa_id
    JOIN ZapisyStudentow zs ON z.zajecia_id = zs.zajecia_id
    WHERE zs.numer_albumu = ? AND NOW() BETWEEN w.data_rozpoczecia AND w.data_zakonczenia
";
$stmt_wybory = $conn->prepare($wybory_sql);
$stmt_wybory->bind_param("i", $numer_albumu);
$stmt_wybory->execute();
$aktywne_wybory = $stmt_wybory->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h1>Wybór Przedmiotów Obieralnych</h1>
<p>Poniżej znajduje się lista aktywnych procesów wyboru przedmiotów, w których możesz wziąć udział.</p>

<table>
    <thead><tr><th>Nazwa wyboru</th><th>Wybór trwa do</th><th>Akcja</th></tr></thead>
    <tbody>
        <?php if (empty($aktywne_wybory)): ?>
            <tr><td colspan="3">Obecnie nie ma żadnych aktywnych wyborów dla Twojej grupy.</td></tr>
        <?php else: ?>
            <?php foreach ($aktywne_wybory as $wybor): ?>
                <tr>
                    <td><?= htmlspecialchars($wybor['nazwa_wyboru']) ?></td>
                    <td><?= htmlspecialchars($wybor['data_zakonczenia']) ?></td>
                    <td><a href="index.php?page=wybory_student_glosuj&wybor_id=<?= $wybor['wybor_id'] ?>" class="btn-add">Przejdź do wyboru</a></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>