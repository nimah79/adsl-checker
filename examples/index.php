<?php

require __DIR__'/../AdslChecker.php';

if(!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['captcha'])) {
    echo AdslChecker::loginAndGetInfo($_POST['username'], $_POST['password'], $_POST['captcha']);
    AdslChecker::logout();
}
else {
    file_put_contents('captcha.jpg', AdslChecker::getCaptcha());
}

?>

<!DOCTYPE html>
<html>
  <head>
    <title></title>
  </head>
  <body>
    <br>
    <img src="captcha.php?rand=<?php echo time() ?>">
    <form method="post">
      <input type="text" name="username" placeholder="username" required>
      <br>
      <input type="password" name="password" placeholder="password" required>
      <br>
      <input type="text" name="captcha" placeholder="captcha" required>
      <input type="submit" name="submit">
    </form>
  </body>
</html>
