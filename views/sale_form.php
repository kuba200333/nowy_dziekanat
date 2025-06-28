<?php
// Plik: views/sale_form.php
?>
<h1>Dodaj Nową Salę</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="add_sala">
    <div class="form-group">
        <label for="budynek">Budynek</label>
        <input type="text" id="budynek" name="budynek" placeholder="np. WIMiI">
    </div>
    <div class="form-group">
        <label for="numer_sali">Numer Sali</label>
        <input type="text" id="numer_sali" name="numer_sali" required placeholder="np. 115A">
    </div>
    <button type="submit">Dodaj Salę</button>
</form>