<?php

declare(strict_types=1);

namespace Xww\Tests\Leech;

use PHPUnit\Framework\TestCase;
use Xww\Leech\ContinuousFill;

class ContinuousFillTest extends TestCase
{
    public function testOpt():array
    {
        $total_amount = 5000;
        $base_amount = 100;
        $open_price = 1900;
        $fee_ratio = 0.0015;
        $position_volume = $base_amount / $open_price;
        $position_free_volume = $position_volume * $fee_ratio;
        $opt =  [
            'total_amount' => $total_amount, //总金额
            'base_amount' => $base_amount, //基础金额
            'position_amount' => $base_amount, //总补仓金额
            'position_avg' => $open_price, //持仓均价
            'position_volume' => $position_volume, //总补仓数量
            'position_fee_volume' =>  $position_free_volume, //手续费占用数量
            'fee_ratio' => $fee_ratio, //手续费率
            'min_amount' => '10', //最新金额
            'drop_ratio' => '1.2,1.6,3.2,3.2,6', //触发补仓跌幅比 数列
            'fill_drop_ratio' => '0.6,1,1.6,2.2,4', //补仓后控制跌幅比 数列
            'latest_fill_price' => '1900', //上次补仓价格
            'depth_price' => '1900', //行情价格
        ];
        $this->assertIsArray($opt);
        return $opt;
    }

    /**
     * @depends testOpt
     */
    public function testGeneratePreviewData(array $opt)
    {
        $continuousFill = new ContinuousFill();
        $continuousFill->setOpt($opt);
        $fill_details = $continuousFill->getFillDetails();
        $fill_sequences = $continuousFill->getFillSequences();
        print_r($fill_details);
        print_r($fill_sequences);
        $this->assertIsArray($fill_sequences);

    }
}
