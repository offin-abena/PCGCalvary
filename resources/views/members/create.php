<div class="page-header"><h1>Add New Member</h1></div>



<?php

$formAction = 'members/store';

$submitLabel = 'Register Member';

$isEdit = false;

$member = $_SESSION['old'] ?? [];

unset($_SESSION['old']);

include __DIR__ . '/_form.php';

?>

