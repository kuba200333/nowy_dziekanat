<?php
// Plik: views/stypendia_admin_wnioski.php (Wersja z linkiem do szczegółów)
$nabor_id = (int)$_GET['nabor_id'];

// Pobierz informacje o naborze do nagłówka
$nabor_info = $conn->query("SELECT nazwa_naboru FROM NaborStypendialny WHERE nabor_id = $nabor_id")->fetch_assoc();

// Pobierz wnioski posortowane od najlepszej średniej
$wnioski = $conn->query("SELECT w.*, s.imie, s.nazwisko FROM WnioskiStypendialne w JOIN Studenci s ON w.numer_albumu = s.numer_albumu WHERE w.nabor_id = $nabor_id ORDER BY w.obliczona_srednia DESC")->fetch_all(MYSQLI_ASSOC);

// Policz 10% studentów
$total_studenci = $conn->query("SELECT COUNT(*) as total FROM Studenci")->fetch_assoc()['total'];
$limit_stypendium = ceil($total_studenci * 0.10);
?>
<h1>Wnioski dla: <?= htmlspecialchars($nabor_info['nazwa_naboru']) ?></h1>
<p>Lista złożonych wniosków posortowana wg średniej. System automatycznie zaznacza na zielono <strong>najlepsze <?= $limit_stypendium ?> osób (top 10%)</strong>.</p>
<table>
    <thead><tr><th>L.p.</th><th>Student</th><th>Nr Albumu</th><th>Średnia (Punkty)</th><th>Status Wniosku</th><th>Akcje</th></tr></thead>
    <tbody>
        <?php if(empty($wnioski)): ?>
            <tr><td colspan="6">Brak złożonych wniosków w tym naborze.</td></tr>
        <?php else: ?>
            <?php foreach ($wnioski as $index => $wniosek): ?>
                <tr <?= ($index < $limit_stypendium && $wniosek['status_wniosku'] == 'zlozony') ? 'style="background-color: #d4edda;"' : '' ?>>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($wniosek['imie'] . ' ' . $wniosek['nazwisko']) ?></td>
                    <td><?= $wniosek['numer_albumu'] ?></td>
                    <td><strong><?= htmlspecialchars(number_format($wniosek['obliczona_srednia'], 3)) ?></strong></td>
                    <td><?= htmlspecialchars($wniosek['status_wniosku']) ?></td>
                    <td>
                        <a href="index.php?page=stypendia_admin_szczegoly&wniosek_id=<?= $wniosek['wniosek_id'] ?>" class="btn-add">Szczegóły / Rozpatrz</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>