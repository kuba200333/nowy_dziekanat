<?php
// Plik: views/stypendia_admin_lista.php
$nabory = $conn->query("SELECT n.*, r.nazwa_roku FROM NaborStypendialny n JOIN RokiAkademickie r ON n.rok_akademicki_id = r.rok_akademicki_id ORDER BY n.data_startu DESC")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Zarządzanie Naborami na Stypendia</h1>
<a href="index.php?page=stypendia_admin_form" class="btn-add">Utwórz nowy nabór</a>
<table>
    <thead><tr><th>Nazwa Naboru</th><th>Rok Akademicki</th><th>Okres składania</th><th>Status</th><th>Akcje</th></tr></thead>
    <tbody>
        <?php foreach ($nabory as $nabor): ?>
        <tr>
            <td><?= htmlspecialchars($nabor['nazwa_naboru']) ?></td>
            <td><?= htmlspecialchars($nabor['nazwa_roku']) ?></td>
            <td><?= htmlspecialchars($nabor['data_startu']) ?> - <?= htmlspecialchars($nabor['data_konca']) ?></td>
            <td><?= htmlspecialchars($nabor['status_naboru']) ?></td>
            <td><a href="index.php?page=stypendia_admin_wnioski&nabor_id=<?= $nabor['nabor_id'] ?>" class="btn-add">Zobacz wnioski</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>