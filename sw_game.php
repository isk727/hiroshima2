<?php
if (is_file(dirname(__FILE__).DIRECTORY_SEPARATOR."config.php")) { require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."config.php"); }
if (is_file('./class/Slot.class.php')) { require_once('./class/Slot.class.php'); }
if (is_file('./class/Game.class.php')) { require_once('./class/Game.class.php'); }
$sb = new SlotBean();
$gb = new GameBean();
$slot = null;
$sb->where = "id=".$_GET['id'];
$slots = $sb->getEntries();
$json = "";
if (count($slots) == 1) {
	$slot = $slots[0];
} else {
  header("Location: errorpage.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="HandheldFriendly" content="True" />
  <title>GAME</title>
  <link rel="icon" href="img/favicon.png" type="image/png">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://code.jquery.com/jquery-migrate-3.4.0.min.js" integrity="sha256-mBCu5+bVfYzOqpYyK4jm30ZxAZRomuErKEFJFIyrwvM=" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/izimodal/1.6.1/js/iziModal.min.js"></script>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izimodal/1.6.1/css/iziModal.css">
  <link href="https://use.fontawesome.com/releases/v6.2.0/css/all.css" rel="stylesheet">
  <link rel="stylesheet" href="./css/style.css" media="screen">
  <style>.stheader { font-weight:bold;}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light" style="background-color:#e3f2fd;">
  <div class="container-fluid text-center">
    <a class="navbar-brand" href="#">　<?= $slot->name ?></a>
  </div>
</nav>
<!-- ========================================================================= -->
<?php if ($slot->gkey == "") { ?>
  <table style="margin-left:2em;">
    <tr>
      <td>
        <div class="input-group mb-3">
        <span class="input-group-text" id="inputGroup-sizing-default">ポイント</span>
          <div class="row">
            <div class="col-xs-4">
              <input type="number" id="txt-point" class="form-control" aria-label="Sizing example input" aria-describedby="inputGroup-sizing-default" placeholder="開始ポイント" required>
            </div>
          </div>
        </div>
      </td>
    </tr>
    <tr style="height:2em;"><td></td></tr>
    <tr>
      <td>
        <div class="text-end">
          <button id="btn-start" class="btn btn-success btn-sm btn-top-navi"><i class="fa-solid fa-circle-dollar-to-slot">　開　始</i></button>
          <span>　</span>
          <button id="btn-close" class="btn btn-secondary btn-sm btn-top-navi"><i class="fa-regular fa-circle-xmark">　閉じる</i></button>
        </div>
      </td>
    </tr>
  </table>
<!-- ========================================================================= -->
<?php } else { ?>
  <table style="margin-left:2em; width:80%;">
    <tr>
      <td style="width: 30%"><span class="stheader">開始日時</span></td>
      <td><div class="text-start"><span id="start_at"></span></div></td>
    </tr>
    <tr>
      <td class="warning"><span class="stheader">開始ポイント</span></td>
      <td><span id="start_pt"></span></td>
    </tr>
    <tr>
      <td><span class="stheader">現在ポイント</span></td>
      <td>
        <span id="point"></span>　　（
        <span>RB：</span><span id="rb"></span>　
        <span>BB：</span><span id="bb"></span>）
      </td>
    </tr>
    <tr><td colspan=2> </td></tr>
    <tr>
      <td colspan=2>
        <div class="input-group mb-3">
        ⚠️ このゲームを終了します<br>
          ✳️ よろしければ終了ボタンをクリックしてください
        </div>
      </td>
    </tr>
    <tr>
      <td colspan=2>
        <button id="btn-stop" class="btn btn-danger btn-sm btn-top-navi"><i class="fa-solid fa-check-to-slot">　終　了</i></button>
        <span>　</span>
        <button id="btn-close" class="btn btn-secondary btn-sm btn-top-navi"><i class="fa-regular fa-circle-xmark">　閉じる</i></button>
        <br><br>
        <button class="btn-sm btn-top-navi" style="visibility:hidden"></button>
        <span>　</span>
        <button id="btn-reset" class="btn btn-warning btn-sm btn-top-navi"><i class="fa-solid fa-hand"></i>強制終了</i></button>
      </td>
    </tr>
  </table>
<?php } ?>
<!-- ========================================================================= -->
<input type="hidden" value="<?= $slot->gkey ?>" id="gkey">
<iframe id="webiopi" style="display:none;"></iframe>
<script>
const sleep = waitTime => new Promise(resolve => setTimeout(resolve, waitTime));
const getPort = (ip) => { return "<?= _PORT_PREFIX ?>" + ip.split('.')[3]; };
const winClose = () => { parent.location.reload(); parent.$.fn.colorbox.close(); };
/* ゲーム開始 ***************************** */
const startGame = (id, ip, point) => {
  $("#btn-start").prop("disabled", true);
  ajaxStartGame(id, ip, point);
  sleep(200).then(()=>{ 
    let gkey = $('#gkey').val();
    let url = "http://" + ip + ":" + getPort(ip) + "/api/webiopi.html?point=" + point + "&gkey=" + gkey + "&macro=start";
    $('#webiopi').attr('src', url);
    sleep(200).then(()=>{ winClose(); });
  });
};
const ajaxStartGame = (id, ip, point) => {
  let data = { "id" : id, "ip" : ip, "point" : point,};
  let json = JSON.stringify(data);
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "./ajax/ajaxStartGame.php");
  xhr.setRequestHeader("content-type", "application/x-www-form-urlencoded;charset=UTF-8");
  xhr.send(json);
  xhr.onreadystatechange = function () {
    try {
      if (xhr.readyState == 4 && xhr.status == 200) {
        let result = JSON.parse(xhr.response);
        $("#gkey").val(result.value);
      } else {
      }
    } catch (e) {
    }
  };
};
/* ゲーム終了 ***************************** */
const stopGame = (id, ip, ft) => {
  $("#btn-stop").prop("disabled", true);
  let gkey = $('#gkey').val();
  if (ft == 0) {
    let url = "http://" + ip + ":" + getPort(ip) + "/api/webiopi.html?point=0&gkey=" + gkey + "&macro=stop";
    $('#webiopi').attr('src', url);
  }
  //ajaxStopGame(id, ip, $("#gkey").val());
  //ajaxStopGame(id, ip, gkey, ft);
  sleep(200).then(()=>{ 
    ajaxStopGame(id, ip, gkey, ft);
    sleep(200).then(()=>{ winClose(); });
  });
};
const ajaxStopGame = (id, ip, gkey, ft) => {
  let data = { "id" : id, "ip" : ip, "gkey" : gkey, "ft" : ft};
  let json = JSON.stringify(data);
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "./ajax/ajaxStopGame.php");
  xhr.setRequestHeader("content-type", "application/x-www-form-urlencoded;charset=UTF-8");
  xhr.send(json);
  xhr.onreadystatechange = function () {
    try {
      if (xhr.readyState == 4 && xhr.status == 200) {
        let result = JSON.parse(xhr.response);
        $("#gkey").val(result.value);
      } else {
        $("#gkey").val("23");
      }
    } catch (e) {
        $("#gkey").val("67");
    }
  };
};
/* ゲーム状態取得 ***************************** */
const ajaxGetGameStatus = (ip) => {
  let data = {"ip" : ip};
  let json = JSON.stringify(data);
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "./ajax/ajaxGetGameStatus.php");
  xhr.setRequestHeader("content-type", "application/x-www-form-urlencoded;charset=UTF-8");
  xhr.send(json);
  xhr.onreadystatechange = function () {
    try {
      if (xhr.readyState == 4 && xhr.status == 200) {
        let result = JSON.parse(xhr.response);
        let arr = result.value.split(",");
        $("#start_at").html(arr[0]);
        $("#start_pt").html(arr[1]);
        $("#point").html(arr[2]);
        $("#rb").html(arr[3]);
        $("#bb").html(arr[4]);
      } else {
      }
    } catch (e) {
    }
  };
};

$(function() {
<?php if ($slot->gkey != "") { ?>
  let ip = "<?= $slot->ip ?>";
  $("#webiopi").attr("src", "http://" + ip + ":" + getPort(ip) + "/api/webiopi.html?point=1&gkey=2&macro=check");
  sleep(500).then(()=>{ 
    ajaxGetGameStatus(ip);
  });
<?php } ?>

$("#btn-start").on('click', function () {
    let point = $("#txt-point").val();
    if (point < 10 || point > 99999) {
      swal({
        text: "適切な開始ポイント(10〜100,000)を入力してください",
        icon: "error",
      });
      return false;
    }
    startGame(<?= $slot->id ?>, '<?= $slot->ip ?>', point);
  });

  $("#btn-stop").on('click', function () { // 終了
    stopGame(<?= $slot->id ?>, '<?= $slot->ip ?>', 0);
  });

  $("#btn-reset").on('click', function () { // 強制終了
      swal({
        title: "強制終了しますか?",
        text: "この台を強制終了します。今回のゲームデータは保存されません!",
        icon: "warning",
        buttons: ['中止', '強制終了する'],
        dangerMode: true,
      }).then(function(isConfirm) {
        if (isConfirm) {
            stopGame(<?= $slot->id ?>, '<?= $slot->ip ?>', 1);
        }
      })
  });

  $("#btn-close").on('click', function () { // 閉じる
    winClose();
  });
});
</script>
</body>
</html>