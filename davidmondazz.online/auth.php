<?php
ini_set('session.save_path', '/home/mcgkxyz/mcgk.site/temp');
session_start();
include 'db.php';
date_default_timezone_set("Africa/Cairo");
$dateNow = date('d M, Y h:i a');

// Add baseImgPath setup at the beginning of the file after includes
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

// Get the path of the current script
$scriptPath = $_SERVER['SCRIPT_NAME'];
$baseDirectory = dirname($scriptPath);
if ($baseDirectory === '/' || $baseDirectory === '\\') {
    $baseDirectory = '/';
} elseif (substr($baseDirectory, -1) !== '/') {
    $baseDirectory .= '/';
}

// Construct the base URL including the directory
$baseURL = $protocol . "://" . $host . $baseDirectory;
$baseImgPath = rtrim($baseURL, '/');

if (isset($_POST['username']) && isset($_POST['password'])) {

    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);

    if (empty($username)) {
        header("Location: auth.php?error=Username_is_missing");
    } elseif (empty($password)) {
        header("Location: auth.php?error=Password_is_empty");
    } else {
        $stmt = $connect->prepare("SELECT * FROM auth WHERE username=?");
        $stmt->execute([$username]);

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch();
            $user_name = $user['username'];
            $user_rank = $user['rank'];
            $user_password = $user['password'];

            if ($username === $user_name) {
                if (password_verify($password, $user_password)) {
                  
                    $_SESSION['username'] = $user_name;
                    $_SESSION['rank'] = $user_rank;
                    $expiry = time() + 36000;
                    setcookie('username', $user_name, $expiry);

                    $updateLastLogin = $connect->prepare("UPDATE auth set last_login=? where username=?");
                    $updateLastLogin->execute([$dateNow,$username]);

                    if (empty($_SESSION['referLink'])) {
                        header("Location: index.php");
                    } else {
                        header("Location: " . $_SESSION['referLink']);
                    }

                } else {
                    header("Location: auth.php?error=Incorrect_Info!");
                }
            } else {
                header("Location: auth.php?error=Incorrect_Info!");
            }

        } else {
            header("Location: auth.php?error=Incorrect_Info!");
        }

    }
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>System Authorization</title>
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<script src="js/jquery.min.js"></script>
<style>
	#keypad {
  width: 390px;
  margin: 50px auto;
  background: #fff;
  padding: 35px 25px;
  text-align: center;
  box-shadow: 0px 5px 5px -0px rgba(0, 0, 0, 0.3);
  border-radius: 5px;
}

input[type="password"] {
  padding: 0 40px;
  border-radius: 5px;
  width: 280px;
  margin: auto;
  border: 1px solid rgb(228, 220, 220);
  outline: none;
  font-size: 40px;
  color: transparent;
  text-shadow: 0 0 0 rgb(71, 71, 71);
  text-align: center;
}

input:focus {
  outline: none;
}

.pinButton {
  border: none;
  background: none;
  font-size: 1.5em;
  border-radius: 50%;
  height: 60px;
  font-weight: 550;
  width: 60px;
  color: transparent;
  text-shadow: 0 0 0 rgb(102, 101, 101);
  margin: 7px 20px;
}

.clear,
.enter {
  font-size: 1em !important;
}

.pinButton:hover {
  box-shadow: #506ce8 0 0 1px 1px;
}
.pinButton:active {
  background: #506ce8;
  color: #fff;
}

.clear:hover {
  box-shadow: #ff3c41 0 0 1px 1px;
}

.clear:active {
  background: #ff3c41;
  color: #fff;
}

.enter:hover {
  box-shadow: #47cf73 0 0 1px 1px;
}

.enter:active {
  background: #47cf73;
  color: #fff;
}

</style>

</head>
<body>
		<div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
			<form action="auth.php" method="post" class="p-5 rounded shadow" style="width: 27rem;">
				<h1 class="text-center pb-5 display-4"><img src="<?php echo $baseImgPath; ?>/img/login.png"></h1>

				<?php if (isset($_GET['error'])) {?>
					<div class="alert alert-danger" role="alert">
					  <?php echo '<center>' . $_GET['error'] . '</center>'; ?>
					</div>
				<?php }?>

				<div class="mb-3">
					<label for="exampleInputEmail1"
							class="form-label">Welcome:
					</label>
					<input type="text" name="username"
							class="form-control" value="masterbob" style="text-align: center;font-weight:bold;color:#1775af;">
				</div>

				<center>

  <form id="keypad">
    <input type="password" id="password" name="password"/></br><br>
    <input type="button" value="1" id="1" class="pinButton calc"/>
    <input type="button" value="2" id="2" class="pinButton calc"/>
    <input type="button" value="3" id="3" class="pinButton calc"/><br>
    <input type="button" value="4" id="4" class="pinButton calc"/>
    <input type="button" value="5" id="5" class="pinButton calc"/>
    <input type="button" value="6" id="6" class="pinButton calc"/><br>
    <input type="button" value="7" id="7" class="pinButton calc"/>
    <input type="button" value="8" id="8" class="pinButton calc"/>
    <input type="button" value="9" id="9" class="pinButton calc"/><br>
    <input type="button" value="clear" id="clear" class="pinButton clear"/>
    <input type="button" value="0" id="0 " class="pinButton calc"/>
    <input type="submit" value="enter" id="enter" class="btn btn-primary"/>
  </form>

</center>


			</form>
		</div>


<script>
	$(document).ready(function () {

const input_value = $("#password");


//add password
$(".calc").click(function () {
  let value = $(this).val();
  field(value);
});

function field(value) {
  input_value.val(input_value.val() + value);
}

$("#clear").click(function () {
  input_value.val("");
});


});

</script>

</body>
</html>

