<?php
if (is_file('../class/Game.class.php')) { require_once('../class/Game.class.php'); }
$gb = new GameBean();
$req = json_decode(file_get_contents("php://input"), true);
$ret = "";
if ($req['ft'] == 1) {
  $ret = $gb->forceTermGame($req['id'], $req['gkey']);
} else {
  $ret = $gb->stopGame($req['id'], $req['ip'], $req['gkey']);
}
$result =[ "value" => $ret, ];
$json = json_encode($result, JSON_UNESCAPED_UNICODE);
header("Content-Type: application/json; charset=UTF-8");
echo $json;
exit;
?>
