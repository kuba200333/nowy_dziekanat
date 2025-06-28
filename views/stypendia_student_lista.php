<?php
// Plik: views/stypendia_student_lista.php

$numer_albumu = $_SESSION['user_id'];

// Pobierz aktywne nabory na stypendia
$nabory_sql = "
    SELECT n.*, r.nazwa_roku 
    FROM NaborStypendialny n
    JOIN RokiAkademickie r ON n.rok_akademicki_id = r.rok_akademicki_id
    WHERE NOW() BETWEEN n.data_startu AND n.data_konca AND n.status_naboru = 'otwarty'
";
$aktywne_nabory = $conn->query($nabory_sql)->fetch_all(MYSQLI_ASSOC);

// Sprawdź, na które nabory student już złożył wniosek
$zlozone_wnioski_sql = "SELECT nabor_id FROM WnioskiStypendialne WHERE numer_albumu = ?";
$stmt_zlozone = $conn->prepare($zlozone_wnioski_sql);
$stmt_zlozone->bind_param("i", $numer_albumu);
$stmt_zlozone->execute();
$zlozone_result = $stmt_zlozone->get_result()->fetch_all(MYSQLI_ASSOC);
$zlozone_ids = array_column($zlozone_result, 'nabor_id');
?>

<h1>Wnioskowanie o Stypendium</h1>
<p>Poniżej znajduje się lista aktywnych naborów na stypendia. Możesz złożyć wniosek w każdym naborze, w którym jeszcze nie brałeś/aś udziału.</p>

<table>
    <thead>
        <tr>
            <th>Nazwa Naboru</th>
            <th>Rok Akademicki</th>
            <th>Okres składania wniosków</th>
            <th>Status / Akcja</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($aktywne_nabory)): ?>
            <tr><td colspan="4">Obecnie nie ma żadnych aktywnych naborów na stypendia.</td></tr>
        <?php else: ?>
            <?php foreach ($aktywne_nabory as $nabor): ?>
                <tr>
                    <td><?= htmlspecialchars($nabor['nazwa_naboru']) ?></td>
                    <td><?= htmlspecialchars($nabor['nazwa_roku']) ?></td>
                    <td><?= htmlspecialchars($nabor['data_startu']) ?> - <?= htmlspecialchars($nabor['data_konca']) ?></td>
                    <td>
                        <?php if (in_array($nabor['nabor_id'], $zlozone_ids)): ?>
                            <span class="status-message success" style="padding: 5px 10px; border-radius: 5px;">Wniosek został już złożony</span>
                        <?php else: ?>
                            <a href="index.php?page=stypendia_student_form&nabor_id=<?= $nabor['nabor_id'] ?>" class="btn-add">Złóż wniosek</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>