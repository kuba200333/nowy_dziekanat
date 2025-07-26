<?php
// Plik: views/studenci_form.php
?>
<h1>Dodaj Nowego Studenta</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="add_student">
    <div class="form-group">
        <label for="numer_albumu">Numer Albumu</label>
        <input type="number" id="numer_albumu" name="numer_albumu" required>
    </div>
    <div class="form-group">
        <label for="s_imie">Imię</label>
        <input type="text" id="s_imie" name="imie" required>
    </div>
    <div class="form-group">
        <label for="s_nazwisko">Nazwisko</label>
        <input type="text" id="s_nazwisko" name="nazwisko" required>
    </div>
    <div class="form-group">
        <label for="s_email">Email</label>
        <input type="email" id="s_email" name="email" required>
    </div>
    <div class="form-group">
        <label for="rok_rozpoczecia">Rok Rozpoczęcia Studiów</label>
        <input type="number" id="rok_rozpoczecia" name="rok_rozpoczecia" placeholder="RRRR" required>
    </div>
    <div class="form-group">
        <label for="status_studenta">Status</label>
        <select id="status_studenta" name="status_studenta" required>
            <option value="aktywny">Aktywny</option>
            <option value="urlop">Urlop</option>
            <option value="absolwent">Absolwent</option>
            <option value="skreślony">Skreślony</option>
        </select>
    </div>
    <button type="submit">Dodaj Studenta</button>
</form>