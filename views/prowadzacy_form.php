<?php
// Plik: views/prowadzacy_form.php
?>
<h1>Dodaj Nowego Prowadzącego</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="add_prowadzacy">
    <div class="form-group">
        <label for="p_imie">Imię</label>
        <input type="text" id="p_imie" name="imie" required>
    </div>
    <div class="form-group">
        <label for="p_nazwisko">Nazwisko</label>
        <input type="text" id="p_nazwisko" name="nazwisko" required>
    </div>
    <div class="form-group">
        <label for="p_tytul">Tytuł Naukowy</label>
        <input type="text" id="p_tytul" name="tytul_naukowy" placeholder="np. dr, prof. dr hab.">
    </div>
    <div class="form-group">
        <label for="p_email">Email</label>
        <input type="email" id="p_email" name="email" required>
    </div>
    <button type="submit">Dodaj Prowadzącego</button>
</form>