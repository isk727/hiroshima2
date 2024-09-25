<?php
if (is_file(dirname(__FILE__).DIRECTORY_SEPARATOR."DatasetBean.class.php")) { require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."DatasetBean.class.php"); }
class GameBean extends DatasetBean {
  public function makeQuery() {
    $query = "select ".
             "id,".
             "gkey,".
             "slot_id,".
             "in_point,".
             "out_point,".
             "diff_point,".
             "num_rb,".
             "num_bb,".
             "num_bw,".
             "bw_total,".
             "bw_rb,".
             "bw_bb,".
             "start_at,".
             "stop_at,".
             "disable ".
             "from game ".
             "where ".$this->where." ";
    if ($this->orderby == '') {
        $query .= "order by id";
    } else {
        $query .= "order by ".$this->orderby;
    }
    if ($this->limit > 0) {
        $query .= " limit ".$this->limit;
    }
    return $query;
  }

  // ラズパイの生存確認
  private function checkPing($host) {
    $r = exec(sprintf('ping -c 1 -W 5 %s', escapeshellarg($host)), $res, $rval);
    return ($rval === 0);
  }
    
  // gamekeyの生成
  private function getGameKey() {
    date_default_timezone_set('Asia/Tokyo');
    $date = new DateTimeImmutable();
    return $date->format('ymdHis') . substr(base_convert(md5(uniqid()), 16, 36), 0, 4); // length = 4
  }

  public function addGame($sid, $ip, $point) {
    if (_CHECKPING == 1) {
      if (!$this->checkPing($ip)) {
        return _NOACTIVE;//        return "N/A";
      }
    }
    $pdo = $this->getPDO();
    if ($pdo == null) {
      return _NOACTIVE;
    }
    $gkey = $this->getGameKey();
    $sql = "insert into game (gkey,slot_id,in_point) values (:gkey,:sid,:point)";
    $sth = $pdo->prepare($sql);
    $sth->bindValue(':gkey', $gkey);
    $sth->bindValue(':sid', $sid);
    $sth->bindValue(':point', $point);
    $res = $sth->execute();
    if($res) {
      $sql = "update slot set gkey=:gkey where id=:sid"; // gameは不要
      $sth = $pdo->prepare($sql);
      $sth->bindValue(':gkey', $gkey);
      $sth->bindValue(':sid', $sid);
      $sth->execute();
    }
    return $gkey;// gamekey を返す
  }

  // gameデータからトップページ表示用csvを返す
  public function getGameData($id) {
    $pdo = $this->getPDO();
    if ($pdo == null) {
      return _NOACTIVE;
    }
    $qry = "select ifnull(sum(in_point),0)||','||ifnull(sum(out_point),0)||','||ifnull(sum(num_rb),0)||','||ifnull(sum(num_bb),0) as gdata from game where slot_id=:id and disable=0 and stop_at is not null";
    $stmt = $pdo->prepare($qry);
    $stmt->bindParam( ':id', $id, PDO::PARAM_INT);
    $res = $stmt->execute();
    if( $res ) {
      $data = $stmt->fetch();
      return $data['gdata'];
    } else {
      return _NOACTIVE;
    }
  }

  public function getEntries() {
    $pdo = $this->getPDO();
    if ($pdo == null) {
      exit;
    }
    $stmt = $pdo->query($this->makeQuery());
    $rows = $stmt->fetchAll();
    $entries = array();
    foreach ($rows as $rs) {
      $game = new Game($rs);
      array_push($entries, $game);
    }
    return $entries;
  }

  // ゲーム終了
  public function stopGame($sid, $ip, $gkey) {
    if (_CHECKPING == 1) {
      if (!$this->checkPing($ip)) {
        return _NOACTIVE;
      }
    }
    $pdo = $this->getPDO();
    if ($pdo == null) {
      return _NOACTIVE;
    }
    $json = $this->getJson($ip);
    $sql = "update game set in_point = :in_point, out_point = :out_point, num_rb = :num_rb, num_bb = :num_bb, num_bw = :num_bw, bw_rb = :bw_rb, bw_bb = :bw_bb, start_at = :start_at, stop_at = :stop_at where gkey = :gkey";
    $sth = $pdo->prepare($sql);
    $sth->bindValue(':gkey', $json['gkey']);
    $sth->bindValue(':in_point', (int)$json['start_pt'], PDO::PARAM_INT);
    $sth->bindValue(':out_point', (int)$json['stop_pt'], PDO::PARAM_INT);
    $sth->bindValue(':num_rb', (int)$json['rb'], PDO::PARAM_INT);
    $sth->bindValue(':num_bb', (int)$json['bb'], PDO::PARAM_INT);
    $sth->bindValue(':num_bw', (int)$json['bw'], PDO::PARAM_INT);

    $sth->bindValue(':bw_rb', $json['bw_rb']);
    $sth->bindValue(':bw_bb', $json['bw_bb']);

    $sth->bindValue(':start_at', $json['start_at']);
    $sth->bindValue(':stop_at', $json['stop_at']);
    $res = $sth->execute();
    if ($res) {
      $bw_total = $this->getTotalBw($json['bw_rb']);
      $sql = "update game set diff_point = (in_point - out_point), bw_total = :bw_total where gkey = :gkey";
      $sth = $pdo->prepare($sql);
      $sth->bindValue(':bw_total', $bw_total, PDO::PARAM_INT);
      $sth->bindValue(':gkey', $json['gkey']);
      $res = $sth->execute();
      $sql = "update slot set gkey='' where id = :id";
      $sth = $pdo->prepare($sql);
      $sth->bindValue(':id', $sid);
      return $sth->execute();  
    } else {
      return _NOACTIVE;
    }
  }

  // ゲーム強制終了
  public function forceTermGame($sid, $gkey) {
    $pdo = $this->getPDO();
    if ($pdo == null) {
      return _NOACTIVE;
    }
    $sql = "delete from game where gkey = :gkey";
    $sth = $pdo->prepare($sql);
    $sth->bindValue(':gkey', $gkey);
    $res = $sth->execute();
    $sql = "update slot set gkey='' where id = :id";
    $sth = $pdo->prepare($sql);
    $sth->bindValue(':id', $sid);
    return $sth->execute();  
  }


  private function getTotalBw($s) {
    $list = explode (",", $s);
    $r = 0;
    foreach($list as $l) {
      $r += $l;
    }
    return $r;
  }
  // 日時をフォーマットして返す（年はYまたはy）
  public function formatDate($strdt) {
    return (new DateTime($strdt))->format('n月j日 H:i:s');    
  }
  
  public function getJaonData($ip) {
    return $this->getJson($ip);
  }
  
  private function getJson($ip) {
    $u = $ip . ":" . _PORT_PREFIX . explode(".", $ip)[3];
    $url = str_replace('_IP_', $u, _JSON_FILE);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res =  curl_exec($ch);
    $json = json_decode($res, true);
    curl_close($ch);
    return $json;
  }
}

class Game {
  public $id = '0';
  public $gkey = '';
  public $slot_id = '';
  public $in_point = 0;
  public $out_point = 0;
  public $diff_point = 0;
  public $num_rb = 0;
  public $num_bb = 0;
  public $num_bw = 0;
  public $bw_total = 0;
  public $bw_rb = "";
  public $bw_bb = "";
  public $start_at = 0;
  public $stop_at = 0;
  public $disable = 0;

  public function __construct() {
    $a = func_get_args();
    $i = func_num_args();
    if (method_exists($this, $f='__construct'.$i)) {
      call_user_func_array(array($this,$f), $a);
    }
  }
  function __construct0() {
  }
  function __construct1($row) {
    $this->id = $row['id'];
    $this->gkey = $row['gkey'];
    $this->slot_id = $row['slot_id'];
    $this->in_point = $row['in_point'];
    $this->out_point = $row['out_point'];
    $this->diff_point = $row['diff_point'];
    $this->num_rb = $row['num_rb'];
    $this->num_bb = $row['num_bb'];
    $this->num_bw = $row['num_bw'];
    $this->bw_total = $row['bw_total'];
    $this->bw_rb = $row['bw_rb'];
    $this->bw_bb = $row['bw_bb'];
    $this->start_at = $row['start_at'];
    $this->stop_at = $row['stop_at'];
    $this->disable = $row['disable'];
  }
}
?>
