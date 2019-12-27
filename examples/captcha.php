<?php

require __DIR__.'/../AdslChecker.php';

header('Content-Type: image/jpeg');
echo AdslChecker::getCaptcha();
