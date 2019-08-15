<?php
/*!-----------------------------------------------------------------
 * ビットコイン自動売買（bitFlyer）
 * 前回価格から任意の価格の増減があった場合に自動売買を行う 
 * ------------------------------------------------------------------
 */

/*!------------------------------------------------
 * 環境設定
 * ------------------------------------------------
 */

// API設定、ファイル設定
define("BITFLYER_API_KEY", '【API Key】');
define("BITFLYER_API_SECRET", '【API Secret】');
define("BITFLYER_API_URI", 'https://api.bitflyer.jp/v1/');
define("PATH_LOG_FILE", __DIR__ . '/data_log.txt');		// ログファイル
define("PATH_DATA_LAST", __DIR__ . '/data_last.txt');	// 前回データ保存：最終価格
define("PATH_DATA_ORDER", __DIR__ . '/data_order.txt');	// 前回データ保存：注文区分 ※BUY or SELL

// 注文パラメタ（判定価格）前回との差分額がこの範囲の場合に注文を行う
$order_sell_price	= array(5000, 500000);			// 売却判定価格（下限～上限）単位：円
$order_buy_price	= array(-500000, -5000);		// 購入判定価格（下限～上限）単位：円

// 注文パラメタ（ひな形設定）
$order_param = array(
	'product_code'		=> 'BTC_JPY',		// 注文プロダクト
	'child_order_type'	=> 'MARKET',		// 指値注文 "LIMIT", 成行注文 "MARKET"
	'side'				=> '',				// 買い注文 "BUY", 売り注文 "SELL" 【判定時に自動設定】
	'price'				=> 0,				// "LIMIT" の場合は価格
	'size'				=> 0.001,			// 注文数量(1～0.001) 単位：bitcoin
	'minute_to_expire'	=> 300,				// 期限切れ時間（分）
	'time_in_force'		=> 'GTC'			// 執行数量条件 "GTC", "IOC", "FOK"
);

// 関数定義（ログ出力）
function putLog($message)
{
	echo date("Y-m-d H:i:s")  . "\t" . $message;
	error_log(date("Y-m-d H:i:s") . "\t" . $message . "\r\n", 3, PATH_LOG_FILE);
}

// 関数定義（注文実行）
function sendChildOrder($arrQuery)
{
	$timestamp = time() . substr(microtime(), 2, 3);
	$body = json_encode($arrQuery);
	$header = array(
		'ACCESS-KEY:'		. BITFLYER_API_KEY,
		'ACCESS-TIMESTAMP:'	. $timestamp,
		'ACCESS-SIGN:'		. hash_hmac(
			'sha256',
			$timestamp . 'POST/' . explode('/', BITFLYER_API_URI, 5)[3] . '/me/sendchildorder' . $body,
			BITFLYER_API_SECRET
		),
		'Content-Type:'		. 'application/json',
		'Content-Length:'	. strlen($body),
	);
	$context = stream_context_create(array(
		'http' => array(
			'method' => 'POST',
			'header' => implode(PHP_EOL, $header),
			'content' => $body,
		)
	));
	$http_response_header = array();
	$result = json_decode(file_get_contents(BITFLYER_API_URI . 'me/sendchildorder', false, $context), true);
	$orderid = $result["child_order_acceptance_id"];
	if (!$orderid) putLog('【APIエラー】' . "\t" . var_export($result, true) . " " . var_export($http_response_header, true) . " " . var_export($body, true));
	return $orderid ? true : false;
}


/*!------------------------------------------------
 * 自動売買処理
 * ------------------------------------------------
 */

// 前回の最終価格・注文区分を取得
$previous_last = intval(file_get_contents(PATH_DATA_LAST));
$previous_order = file_get_contents(PATH_DATA_ORDER);

// 現在の最終価格を取得
$current_data = json_decode(file_get_contents(BITFLYER_API_URI . 'getticker?product_code=BTC_JPY'), true);
$current_last = intval($current_data["ltp"]);

// 前回と現在の差分価格を取得（前回情報がない場合は初期値設定）
if ($previous_order == '') $previous_order = 'SELL';
if ($previous_last === 0) file_put_contents(PATH_DATA_LAST, $current_last);
$diff_last = intval($current_last - $previous_last);
$diff_message = '前回：' . number_format($previous_last) . '（' . $previous_order . '）→今回：' . number_format($current_last) . '（' . sprintf("%+d", $diff_last) . '）';

// 注文処理
$isSendChildOrder = false;
switch ($previous_order) {
	case "BUY":
		$order_param['side'] = 'SELL';
		if ($diff_last > $order_sell_price[0] && $diff_last < $order_sell_price[1])
			$isSendChildOrder = sendChildOrder($order_param);
		break;
	case "SELL":
		$order_param['side'] = 'BUY';
		if ($diff_last > $order_buy_price[0] && $diff_last < $order_buy_price[1])
			$isSendChildOrder = sendChildOrder($order_param);
		break;
	default:
		putLog('【注文エラー】' . "\t" . $diff_message);
}

// 結果出力
if ($isSendChildOrder) {
	file_put_contents(PATH_DATA_ORDER, $order_param['side']);
	file_put_contents(PATH_DATA_LAST, $current_last);
	putLog(($order_param['side'] === 'SELL' ? '【売却】' : '【購入】') . "\t" . $diff_message);
} else {
	putLog('【取引なし】' . "\t" . $diff_message);
}

exit;
