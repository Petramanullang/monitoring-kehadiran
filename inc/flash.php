<?php
function set_flash($type, $msg) {
  $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function get_flash() {
  if (!isset($_SESSION['flash'])) return null;
  $f = $_SESSION['flash'];
  unset($_SESSION['flash']);
  return $f;
}
