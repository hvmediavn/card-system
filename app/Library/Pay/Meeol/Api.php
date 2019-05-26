<?php
namespace App\Library\Pay\Meeol; use App\Library\CurlRequest; use App\Library\Pay\ApiInterface; use Illuminate\Support\Facades\Log; class Api implements ApiInterface { private $url_notify = ''; private $url_return = ''; public function __construct($sp5d3f49) { $this->url_notify = SYS_URL_API . '/pay/notify/' . $sp5d3f49; $this->url_return = SYS_URL . '/pay/return/' . $sp5d3f49; } function goPay($spc8c9ef, $sp4510be, $sp930bb6, $sp9a31c1, $spfa6477) { $sp6f1ff6 = sprintf('%.2f', $spfa6477 / 100); if (!isset($spc8c9ef['appId'])) { throw new \Exception('请设置appId'); } if (!isset($spc8c9ef['key'])) { throw new \Exception('请设置key'); } $sp2688c6 = $spc8c9ef['payway']; $spc63cad = array('amount' => $sp6f1ff6, 'appId' => $spc8c9ef['appId'], 'orderId' => $sp4510be, 'random' => md5(random_bytes(16)), 'tradeType' => $sp2688c6); $spc63cad['sign'] = strtoupper(md5('amount=' . $spc63cad['amount'] . '&appId=' . $spc8c9ef['appId'] . '&key=' . $spc8c9ef['key'] . '&orderId=' . $spc63cad['orderId'] . '&random=' . $spc63cad['random'] . '&tradeType=' . $spc63cad['tradeType'])); $sp7025bb = CurlRequest::post('http://api.meeol.cn/rest/mall/payment/order', json_encode($spc63cad)); $sp6f1294 = json_decode($sp7025bb, true); if (!isset($sp6f1294['status']) || $sp6f1294['status'] !== '0') { Log::error('Pay.Meeol.goPay.order Error: ' . $sp7025bb); throw new \Exception('支付请求失败, 请刷新重试'); } if (substr($sp2688c6, 0, 1) === 'W') { header('Location: /qrcode/pay/' . $sp4510be . '/wechat?url=' . urlencode($sp6f1294['qrcode'])); } elseif (substr($sp2688c6, 0, 1) === 'A') { header('Location: /qrcode/pay/' . $sp4510be . '/aliqr?url=' . urlencode($sp6f1294['qrcode'])); } die; } function verify($spc8c9ef, $sp53cf01) { $sp412d81 = isset($spc8c9ef['isNotify']) && $spc8c9ef['isNotify']; if ($sp412d81) { $sp50af7c = json_decode(file_get_contents('php://input'), true); $sp1e1df1 = strtoupper(md5('amount=' . $sp50af7c['amount'] . '&appid=' . $sp50af7c['appid'] . '&key=' . $spc8c9ef['key'] . '&orderId=' . $sp50af7c['orderId'] . '&tradeTime=' . $sp50af7c['tradeTime'] . '&tradeType=' . $sp50af7c['tradeType'])); if ($sp1e1df1 === $sp50af7c['sign']) { $sp6f1ff6 = (int) round($sp50af7c['amount'] * 100); $sp53cf01($sp50af7c['orderId'], $sp6f1ff6, $sp50af7c['passTradeNo']); echo 'success'; return true; } else { Log::error('Pay.Meeol.verify notify sign error, post: ' . file_get_contents('php://input')); echo 'error'; } } else { if (!empty($spc8c9ef['out_trade_no'])) { $spc63cad = array('appId' => $spc8c9ef['appId'], 'orderId' => $spc8c9ef['out_trade_no'], 'random' => md5(random_bytes(16))); $spc63cad['sign'] = strtoupper(md5('appId=' . $spc8c9ef['appId'] . '&key=' . $spc8c9ef['key'] . '&orderId=' . $spc63cad['orderId'] . '&random=' . $spc63cad['random'])); $spc63cad = json_encode($spc63cad); $sp7025bb = CurlRequest::post('http://api.meeol.cn/rest/mall/payment/query', $spc63cad); $sp6f1294 = json_decode($sp7025bb, true); if (!isset($sp6f1294['status'])) { Log::error('Pay.Meeol.verify Error: ' . $sp7025bb); } if ($sp6f1294['status'] === '0') { $sp6f1ff6 = (int) round($sp6f1294['amount'] * 100); $sp53cf01($sp6f1294['orderId'], $sp6f1ff6, $sp6f1294['passTradeNo']); return true; } Log::debug('Pay.Meeol.verify debug, req:' . $spc63cad . 'ret:' . $sp7025bb); return false; } else { throw new \Exception('请传递订单编号'); } } return false; } }