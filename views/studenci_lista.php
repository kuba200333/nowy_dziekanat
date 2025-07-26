<?php
// Plik: views/studenci_lista.php (Wersja bez kolumny Akcje)
$sql = "SELECT numer_albumu, imie, nazwisko, email, rok_rozpoczecia_studiow, status_studenta FROM Studenci ORDER BY nazwisko, imie";
$result = $conn->query($sql);
?>
<h1>Lista Studentów</h1>
<p>Kliknij na imię i nazwisko studenta, aby zobaczyć jego pełny profil i zarządzać danymi.</p>
<a href="index.php?page=studenci_form" class="btn-add">Dodaj nowego studenta</a>
<table>
    <thead>
        <tr>
            <th>Nr Albumu</th>
            <th>Imię i Nazwisko</th>
            <th>Email</th>
            <th>Rok Rozpoczęcia</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['numer_albumu']) ?></td>
                    <td>
                        <a href="index.php?page=student_profil&numer_albumu=<?= $row['numer_albumu'] ?>">
                            <?= htmlspecialchars($row['imie'] . ' ' . $row['nazwisko']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['rok_rozpoczecia_studiow']) ?></td>
                    <td><?= htmlspecialchars($row['status_studenta']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">Brak studentów w bazie danych.</td></tr>
        <?php endif; ?>
    </tbody>
</table>