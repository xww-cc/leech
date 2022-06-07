策略思路
## 控制持仓均价与行情价的跌幅进行补仓  场景配置如下：
> 1. 控制总金额 连续补仓配置 
```
include "./vendor/autoload.php";

use Xww\Leech\ContinuousFill;

$continuousFill = new ContinuousFill();

$opt = [
    'total_amount' => '5000', //总金额
    'base_amount' => '100', //基础金额
    'position_amount' => '100', //总补仓金额
    'position_avg' => '3000', //持仓均价
    'position_volume' => '0.03333333', //总补仓数量
    'position_fee_volume' => '0.00005', //手续费占用数量
    'fee_ratio' => '0.0015', //手续费率
    'min_amount'=>'10', //最新金额
    'drop_ratio' => '1.2,1.6,2.0,6', //触发补仓跌幅比 数列
    'fill_drop_ratio' => '0.6,1,1.2,4', //补仓后控制跌幅比 数列
    'last_fill_price' => '3000', //上次补仓价格
    'depth_price' => '3000', //行情价格
];

$continuousFill = new ContinuousFill();
$continuousFill->setOpt($opt);

$fill_datas = $continuousFill->getFillDatas();
$fill_sequences = $continuousFill->getFillSequences();
$fill_details = $continuousFill->getFillDetails();

print_r($fill_details);
print_r($fill_sequences);
```

> 2. 单次补仓配置
```
include "./vendor/autoload.php";

use Xww\Leech\SingleFill;

$opt = [
    'total_amount' => '5000', //总金额
    'base_amount' => '100', //基础金额
    'position_amount' => '100', //总补仓金额
    'position_avg' => '3000', //持仓均价
    'position_volume' => '0.03333333', //总补仓数量
    'position_fee_volume' => '0.00005', //手续费占用数量
    'fee_ratio' => '0.0015', //手续费率
    'min_amount' => '10', //最小金额限制
    'drop_ratio' => '1.2', //触发补仓跌幅比
    'fill_drop_ratio' => '0.6', //补仓后控制跌幅比
    'last_fill_price' => '3000', //上次补仓价格
    'depth_price' => '3000', //行情价格
];
$singleFill = new SingleFill();
$singleFill->setOpt($opt);
$singleFill->updateDepthPrice(2000);//控制行情价格
$fill_data = $singleFill->getFillData();
$fill_detail = $singleFill->getAfterFillDetails();
print_r($fill_data);
print_r($fill_detail);
```