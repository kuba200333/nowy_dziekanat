<?php
// Plik: views/przebieg_studiow_widok.php
// Ten plik jest dołączany w student_profil.php, więc ma dostęp do zmiennej $numer_albumu

$przebieg = $conn->query("
    SELECT ps.*, r.nazwa_roku 
    FROM PrzebiegStudiow ps 
    JOIN RokiAkademickie r ON ps.rok_akademicki_id = r.rok_akademicki_id
    WHERE ps.numer_albumu = $numer_albumu ORDER BY ps.semestr ASC
")->fetch_all(MYSQLI_ASSOC);
?>
<h3>Przebieg Studiów</h3>
<table>
    <thead><tr><th>Semestr</th><th>Rok Akademicki</th><th>Status</th><th>Średnia</th><th>ECTS</th><th>Akcje</th></tr></thead>
    <tbody>
        <?php foreach($przebieg as $wpis): ?>
        <tr>
            <td><?= $wpis['semestr'] ?></td>
            <td><?= htmlspecialchars($wpis['nazwa_roku']) ?></td>
            <td><strong><?= htmlspecialchars($wpis['status_semestru']) ?></strong></td>
            <td><?= htmlspecialchars($wpis['srednia_semestralna']) ?></td>
            <td><?= htmlspecialchars($wpis['zdobyte_ects']) ?></td>
            <td>
                <?php if ($wpis['status_semestru'] == 'zaliczony'): ?>
                    <form action="handler.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="rejestruj_na_kolejny_semestr">
                        <input type="hidden" name="numer_albumu" value="<?= $numer_albumu ?>">
                        <input type="hidden" name="aktualny_semestr" value="<?= $wpis['semestr'] ?>">
                        <button type="submit" class="btn-add">Zarejestruj na nast. semestr</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>