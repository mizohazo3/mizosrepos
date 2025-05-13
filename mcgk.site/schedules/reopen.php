    <?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $lock = $con->prepare("UPDATE tasklist set status=?, last_lock=?, lasttask=? where id=?");
    $lock->execute(['open', '', '', $id]);

}
