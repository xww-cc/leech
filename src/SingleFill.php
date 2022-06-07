<?php

namespace Xww\Leech;

class SingleFill
{
    public $fillStatus = false; //是否达到补仓跌幅
    public $newOpt = [];

    public $opt = [
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
        'latest_fill_price' => '3000', //上次补仓价格
        'depth_price' => '3000', //行情价格
    ];

    //统一配置;
    public function setOpt($opt = [])
    {
        $this->opt = array_merge($this->opt, $opt);
    }

    //设置行情价格
    public function updateDepthPrice($depth_price)
    {
        $this->opt['depth_price'] = $depth_price;
    }

    //获取当前仓位跌幅
    public function getPositionDropRatio()
    {
        //跌幅 = ( 当前持仓均价 - 当前行情价格) / 当前持仓均价 * 100 
        $o = $this->opt;
        $drop_ratio = ($o['position_avg'] - $o['depth_price']) / $o['position_avg'] * 100;
        if ($drop_ratio < 0) {
            $drop_ratio = 0;
        }
        $drop_ratio = number_format($drop_ratio, 2);

        //是否满足补仓
        if ($drop_ratio < $o['drop_ratio']) {
            $this->fillStatus = false;
        } else {
            if ($o['position_amount'] + $o['min_amount'] <= $o['total_amount']) {
                $this->fillStatus = true;
            } else {
                $this->fillStatus = false;
            }
        }
        return $drop_ratio;
    }

    //获取下次补仓行情价格 阀值
    public function getNextFillDepthPrice()
    {
        //跌幅 = ( 当前持仓均价 - 当前行情价格) / 当前持仓均价 * 100 
        //跌幅*持仓均价/100 = 持仓均价 - 当前行情价
        //行情价格 = 持仓均价*(1- 跌幅/100)
        $o = $this->opt;
        $next_fill_depth_price = $o['position_avg'] * (1 - $o['drop_ratio'] / 100);
        return $next_fill_depth_price;
    }

    //补仓后持仓均价
    public function getFillPositionAvg()
    {
        //跌幅 = (当前持仓均价 - 当前行情价格) / 当前持仓均价 * 100 
        //当前持仓均价 - 跌幅/100*当前持仓均价 = 当前行情价格
        //当前持仓均价 = 当前行情价格/(1-跌幅/100)
        //总金额 = 旧的仓位金额 + 补仓金额
        //补仓金额 = 补仓均价*补仓数量
        //总金额 = 旧的仓位金额 + 补仓价格*补仓数量
        //仓位数量= 旧的仓位数量+补仓数量*(1-手续费率)
        //当前补仓价格 = 当前行情价格
        //仓位总补仓数量 = 旧的总补仓数量 + 补仓数量
        //仓位均价 = (旧的仓位金额 + 补仓价格*补仓数量)/(旧的总补仓数量 + 补仓数量)
        //仓位均价*(旧的总补仓数量+ 补仓数量) = (旧的仓位金额 + 补仓价格*补仓数量)
        //仓位均价*旧的补仓数量 + 仓位均价*补仓数量 = 旧的仓位金额 + 补仓价格*补仓数量
        //旧的仓位金额 - 仓位均价*旧的补仓数量 = 仓位均价*补仓数量-补仓价格*补仓数量
        //补仓数量 = (旧的仓位金额 - 仓位均价*旧的补仓数量)/(仓位均价-补仓价格)
        //仓位均价 = 当前行情价格/(1+跌幅/100)
        $o = $this->opt;
        $fill_position_avg = $o['depth_price'] / (1 - $o['fill_drop_ratio'] / 100);
        return $fill_position_avg;
    }

    //是否可以补仓
    public function hasFill()
    {
        $this->getPositionDropRatio();
        return $this->fillStatus;
    }

    //获取补仓数据
    public function getFillData()
    {
        $this->newOpt = [];
        $o = $this->opt;
        //是否满足补仓
        if ($this->hasFill()) {
            //仓位均价 = 当前行情价格/(1-跌幅/100)
            //补仓价格 = 当前行情价格
            //补仓数量 = (旧的仓位金额 - 仓位均价*旧的补仓数量)/(仓位均价-补仓价格)
            $fill_position_avg = $this->getFillPositionAvg();
            $fill_price = $o['depth_price'];
            $fill_volume = ($o['position_amount'] - $fill_position_avg * $o['position_volume']) / ($fill_position_avg - $fill_price);
            $fill_amount =  $fill_price * $fill_volume;
            $position_amount = $o['position_amount'] + $fill_amount;
            if ($position_amount > $o['total_amount']) {
                $fill_amount = $o['total_amount'] - $o['position_amount'];
                $fill_volume =  $fill_amount / $fill_price;
                $position_amount = $o['position_amount'] + $fill_amount;
            }
            
            $position_volume = $o['position_volume'] + $fill_volume;
            $position_avg = $position_amount/$position_volume;
            $residue_position_amount = $o['total_amount'] - $position_amount;
            $fill_fee_volume = $fill_volume * $o['fee_ratio'];
            $position_fee_volume = $o['position_fee_volume'] + $fill_fee_volume;

            $fill_price_ratio = ($o['latest_fill_price'] - $fill_price) / $o['latest_fill_price'] * 100;
            $fill_price_ratio = $this->fortmatNumber($fill_price_ratio);
            $fill_price_fee_ratio =  ($o['latest_fill_price'] - $fill_price * (1 + $o['fee_ratio'])) / $o['latest_fill_price'] * 100;
            $fill_price_fee_ratio = $this->fortmatNumber($fill_price_fee_ratio);
            $fill_amount_ratio = $this->fortmatNumber($fill_amount / $o['base_amount']);
            $fill_drop_ratio= $this->fortmatNumber(($position_avg-$fill_price)/$position_avg*100);

            $o['position_amount'] = $position_amount;
            $o['residue_position_amount'] = $residue_position_amount;
            $o['position_avg'] = $position_avg;
            $o['position_volume'] = $position_volume;
            $o['position_fee_volume'] = $position_fee_volume;
            $o['latest_fill_price'] = $fill_price;
            $o['fill_drop_ratio'] = $fill_drop_ratio;
            $o['fill_data'] = [
                'fill_price' => $fill_price, //补仓价格
                'fill_volume' => $fill_volume, //补仓数量
                'fill_amount' => $fill_amount, //补仓的金额
                'fee_volume' => $fill_amount * $o['fee_ratio'], //手续费 数量
                'fill_price_fee_ratio' => $fill_price_fee_ratio, //补仓价格跌幅比去除手续费
                'fill_price_ratio' => $fill_price_ratio, //补仓价格跌幅比
                'fill_amount_ratio' => $fill_amount_ratio, //补仓基础金额比
            ];
            $this->newOpt = $o;
            return  $o['fill_data'];
        }
        return [];
    }

    public function fortmatNumber($number)
    {
        return intval($number * 100) / 100;
    }

    //获取补仓后的详细数据
    public function getAfterFillDetails()
    {
        return $this->newOpt;
    }
}
