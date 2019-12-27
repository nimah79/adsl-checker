<?php

require __DIR__.'/../AdslChecker.php';

if (!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['captcha'])) {
    echo AdslChecker::loginAndGetInfo($_POST['username'], $_POST['password'], $_POST['captcha']);
    AdslChecker::logout();
}

?>

<!DOCTYPE html>
<html>
  <head>
    <title></title>
  </head>
  <body dir="rtl" lang="fa">
    <br>
    <img src="captcha.php?rand=<?=time()?>">
    <form method="post">
      <input type="text" name="username" placeholder="نام کاربری" dir="ltr" required>
      <br>
      <input type="password" name="password" placeholder="رمز عبور" dir="ltr" required>
      <br>
      <input type="text" name="captcha" placeholder="کد امنیتی" dir="ltr" required>
      <input type="submit" name="submit" value="ورود">
    </form>
  </body>
</html>
