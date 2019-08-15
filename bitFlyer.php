<?php
/*! 
 *  ビットコイン自動売買システム
 **/

	// スケジュール実行日時
	$dtime = date("Y-m-d H:i:s");							// 日時
	$hour  = intval(date("G"));								// 時
	$min   = intval(date("i"));								// 分
	
	// ファイル定義
	$file_log = dirname(__FILE__)."/log.txt";				// ログファイル
	$file_last_rc = dirname(__FILE__)."/last_rec.txt";		// 最終価格
	$file_last_si = dirname(__FILE__)."/last_side.txt";		// 最終区分 BUY or SELL

	// 管理ログ送信
	if( $hour == 7 && $min == 0 ) {
		putMail( "【全体ログ】：" . file_get_contents($file_log) );
		unlink($file_log);
	}

	// 前回売買、前回価格取得
	$pre_si   = file_get_contents($file_last_si);
	$pre_cc   = intval( file_get_contents($file_last_rc) );

	// 最新価格取得
	$file_get_cc  = json_decode( file_get_contents("https://coincheck.com/api/ticker/") ,true);
	$last_cc      = intval( $file_get_cc["last"] );

	// 比較価格
	$diff_cc  = intval( $last_cc  - $pre_cc );
	$diff_msg = $pre_si . ":" . $pre_cc . "→" . $last_cc . "(" . $diff_cc . ")";

	// 取引実行
	orderJudgment();
	echo "<br>OK ".$dtime;

exit;

// 管理者メール
function putMail($msg,$title = "_ログ") {
	$mail_from = "bitcoin@saneicraft.com";
	$mail_to   = "saneicraft@gmail.com";
	$mail_sub  = "【BITCOIN】自動売買システム".$title;
	$mail_body = $msg."\r\n";
	$headers = 'From:'.$mail_from."\r\n".'Reply-To:'.$mail_from."\r\n";
	$ret = mail($mail_to,$mail_sub,$mail_body,$headers);
}

// ログ出力
function putLog($msg) {
	global $dtime,$file_log;
	error_log($dtime." ".$msg."\r\n",3,$file_log);
	echo $msg;
}

// 注文判定
function orderJudgment() {
	global $file_last_si,$file_last_rc,$pre_si, $pre_cc, $last_cc, $diff_cc, $diff_msg;
	switch( $pre_si ){
	case "SELL":
		if( $diff_cc < -100  && $diff_cc > -500000) {
			$para = array(	"rate" => 0,
							"amount" => 0,
							"market_buy_amount" => round( $last_cc  * 0.005 , -1),
							"order_type" => "market_buy",
							"pair" => "btc_jpy",
						);
			$res = sendChildOrder($para);
			file_put_contents($file_last_si,"BUY");
			file_put_contents($file_last_rc,$last_cc);
			putLog("購入：".$diff_msg." ".var_export($res,true) );
			putMail("【購入しました】：".$diff_msg);
		} else {
			putLog("取引なし:".$diff_msg);
		}
		break;
	case "BUY":
		if( $diff_cc > 100   && $diff_cc < 500000) {
			$para = array(	"rate" => 0,
							"amount" => 0.005,
							"order_type" => "market_sell",
							"pair" => "btc_jpy",
						);
			$res = sendChildOrder($para);
			file_put_contents($file_last_si,"SELL");
			file_put_contents($file_last_rc,$last_cc);
			putLog("売却：".$diff_msg." ".var_export($res,true) );
			putMail("【売却しました】：".$diff_msg);
		} else {
			putLog("取引なし:".$diff_msg);
		}
		break;
	default:
		putLog("不正★".$diff_msg);
	}
}

// オーダ実行
function sendChildOrder($arrQuery) {
	$intNonce5 = time();
	$strUrl5 = 'https://coincheck.jp/api/exchange/orders';
	$strAccessKey5 = 'アクセスキー';
	$strAccessSecret5 = '秘密鍵';
	$strMessage5 = $intNonce5 . $strUrl5 . http_build_query($arrQuery);
	$strSignature5 = hash_hmac("sha256", $strMessage5, $strAccessSecret5);
	$header5 = array(
		'Content-Type: application/x-www-form-urlencoded',
		'ACCESS-KEY: '.$strAccessKey5,
		'ACCESS-NONCE: '.$intNonce5,
		'ACCESS-SIGNATURE: '.$strSignature5
	);
	$context5 = array(  'http' => array(
						'method' =>'POST',
						'ignore_errors' => true,
						'header' => implode("\r\n",$header5),
						'content' => http_build_query($arrQuery5)
					));
//	$coincheck = file_get_contents($strUrl5, false, stream_context_create($context5));
	return $coincheck;
}


?>
