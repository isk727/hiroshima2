<?php
if (is_file(dirname(__FILE__)."/common.php")) { require_once(dirname(__FILE__)."/common.php"); }
if (is_file(dirname(__FILE__)."/config.php")) { require_once(dirname(__FILE__)."/config.php"); }
if (is_file('./class/Slot.class.php')) { require_once('./class/Slot.class.php'); }
$slb = new SlotBean();
$slb->where = "id>0";
$slots = $slb->getEntries();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="HandheldFriendly" content="True" />
  <title>UDP2</title>
  <link rel="icon" href="img/favicon.png" type="image/png">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://code.jquery.com/jquery-migrate-3.4.0.min.js" integrity="sha256-mBCu5+bVfYzOqpYyK4jm30ZxAZRomuErKEFJFIyrwvM=" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/izimodal/1.6.1/js/iziModal.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izimodal/1.6.1/css/iziModal.css">
  <link rel="stylesheet" href="./css/style.css" media="screen">
  <link href="https://use.fontawesome.com/releases/v6.2.0/css/all.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">　　　　システム設定</a>
    <button id="btn-back" class="btn btn-primary btn-sm btn-top-navi"><i class="fa-solid fa-backward"> 戻 る</i></button>
  </div>
</nav>
<form id="frm" mame="frm" action="./index.php" method="post"></form>
<div class="container">
  <div class="row">
    <div>
      <ul class="nav nav-tabs" id="myTab" role="tablist" style="display:none">
        <li class="nav-item" role="presentation">
          <a class="nav-link" href="#profile1" id="profile1-tab" data-bs-toggle="tab" role="tab" aria-controls="profile1" aria-selected="false"></a>
        </li> 
      </ul>
      <div class="tab-pane fade" id="profile1" role="tabpanel" aria-labelledby="profile1-tab" style="width:90%">
        <p class="h5 tab-title">　<i class="bi bi-bell-fill"> 筐体一覧</i></p>
        <p class="text-end txt_margin-3">
          <button id="btn-save-cmd1" class="btn btn-primary btn-sm btn-top-slot" onclick="winOpen('sw_slot.php?id=0');"><i class="bi bi-plus-circle-fill"> 追加</i></button>
          
          <span>　</span><a href="raspi-man.html" target="_blank"><button id="btn-manual" style="background:transparent;border:none;"><img src="img/rp.png" width="24"></button></a>

        </p>
        <table id="slot-table" class="table">
          <thead><tr><th>番号</th><th>機種名</th><th>IPアドレス</th><th>詳細</th></tr></thead>
<?php $ct = 0; foreach($slots as $slot) { ?>
          <tr>
            <td><?= $slot->num ?></td>
            <td><?= $slot->name ?></td>
            <td><?= $slot->ip ?></td>
            <td>
              <button id="btn-save-cmd2" class="btn btn-info btn-sm btn-top-slot" onclick="winOpen('sw_slot.php?id=<?= $slot->id ?>');"><i class="bi bi-pencil-square"> 詳細</i></button>
            </td>
          </tr>
<?php } ?>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const winOpen = (url) => {
  var win = window.open(url, "user_window", "width=650,height=400,scrollbars=yes,resizable=yes,status=yes");
  $(win).on('load', function () {
    $(win).on('unload', function () {
      location.reload();
    });
  });
};

$(function() {
  $("#btn-back").on('click', function () { // 戻る
    $('#frm').submit();
  });
  $('.nav-tabs a[href="#profile1"]').tab('show');
  $("#btn-manual").on('click', function () { // ラズパイマニュアル
    let win = window.open("raspi-man.html", "manual_window", "width=900,height=700,scrollbars=yes,resizable=yes,status=yes");
  });
});
</script>
</body>
</html>