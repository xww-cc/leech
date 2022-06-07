<?php
include "./vendor/autoload.php";

use Xww\Leech\ContinuousFill;

$total_amount = 10000;
$base_amount = 100;
$open_price = 4000;
$fee_ratio = 0.0015;
$position_volume = $base_amount / $open_price;
$position_free_volume = $position_volume * $fee_ratio;

$opt = [
    'total_amount' => $total_amount, //总金额
    'base_amount' => $base_amount, //基础金额
    'position_amount' => $base_amount, //总补仓金额
    'position_avg' => $open_price, //持仓均价
    'position_volume' => $position_volume, //总补仓数量
    'position_fee_volume' =>  $position_free_volume, //手续费占用数量
    'fee_ratio' => $fee_ratio, //手续费率
    'min_amount' => '10', //最新金额
    // 'drop_ratio' =>      '0.8,1.2,1.6,6,4.8,4.2,6', //触发补仓跌幅比 数列
    // 'fill_drop_ratio' => '0.5,0.6,1,4,3.8,3,4.2', //补仓后控制跌幅比 数列
    'drop_ratio' =>      '1,2,3', //触发补仓跌幅比 数列
    'fill_drop_ratio' => '0.6,1.4,2.3', //补仓后控制跌幅比 数列
    'latest_fill_price' => $open_price, //上次补仓价格
    'depth_price' => $open_price, //行情价格
];

$continuousFill = new ContinuousFill();
$continuousFill->setOpt($opt);

$fill_datas = $continuousFill->getFillDatas();
$fill_sequences = $continuousFill->getFillSequences();
$fill_details = $continuousFill->getFillDetails();

print_r($fill_details);
print_r($fill_sequences);

// use Xww\Leech\SingleFill;

// $opt = [
//     'total_amount' => $total_amount, //总金额
//     'base_amount' => $base_amount, //基础金额
//     'position_amount' => $base_amount, //总补仓金额
//     'position_avg' => $open_price, //持仓均价
//     'position_volume' => $position_volume, //总补仓数量
//     'position_fee_volume' =>  $position_free_volume, //手续费占用数量
//     'fee_ratio' => $fee_ratio, //手续费率
//     'min_amount' => '10', //最小金额限制
//     'drop_ratio' => '1.2', //触发补仓跌幅比
//     'fill_drop_ratio' => '0.6', //补仓后控制跌幅比
//     'latest_fill_price' => $open_price, //上次补仓价格
//     'depth_price' => $open_price, //行情价格
// ];
// $singleFill = new SingleFill();
// $singleFill->setOpt($opt);
// $singleFill->updateDepthPrice(2000);
// $fill_data = $singleFill->getFillData();
// $fill_detail = $singleFill->getAfterFillDetails();
// print_r($fill_data);
// print_r($fill_detail);

