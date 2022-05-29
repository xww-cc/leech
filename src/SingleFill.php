<?php
class SingleFill
{
    public $fillStatus = false; //是否达到补仓跌幅
    public $newOpt = [];
    public $opt = [
        'total_amount' => '5000', //总金额
        'base_amount' => '100', //基础金额
        'old_position_amount' => '100', //旧的补仓总金额
        'old_position_avg' => '3000', //旧的补仓均价
        'old_position_volume' => '0.03333333', //旧的补仓数量
        'fee_position_volume' => '0.00005', //手续费占用数量
        'fee_ratio' => '0.0015', //手续费率
        'drop_ratio' => '1.2', //触发补仓跌幅比率
        'fill_drop_ratio' => '0.6', //补仓后控制跌幅比率
        'depth_price' => '200', //行情价格
    ];

    //统一配置;
    public function setOpt($opt = [])
    {
        $this->opt = array_merge($this->opt, $opt);
    }

    //设置行情价格
    public function setDepthPrice($depth_price)
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
        if ($drop_ratio >= $o['fill_drop_ratio']) {
            $this->fillStatus = false;
        } else {
            if ($o['old_position_amount'] + $o['min_amount'] > $o['total_amount']) {
                $this->fillStatus = true;
            } else {
                $this->fillStatus = false;
            }
        }

        return $drop_ratio;
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
            $fill_volume = ($o['old_position_amount'] - $fill_position_avg * $o['fill_position_volume']) / ($fill_position_avg - $fill_price);
            $old_position_amount = $o['old_position_amount'] + $fill_price * $fill_volume;
            if ($old_position_amount > $o['total_amount']) {
                $fill_volume =  ($o['total_amount'] - $old_position_amount) / $fill_price;
                $old_position_amount = $o['old_position_amount'] + $fill_price * $fill_volume;
            }
            $old_position_avg = $fill_position_avg;
            $old_position_volume = $o['old_position_volume'] + $fill_volume;
            $residue_position_amount = $o['total_amount'] - $old_position_amount;
            $fee_position_volume = $old_position_volume * $o['fee_ratio'];

            $o['old_position_amount'] = $old_position_amount;
            $o['residue_position_amount'] = $residue_position_amount;
            $o['old_position_avg'] = $old_position_avg;
            $o['old_position_volume'] = $old_position_volume;
            $o['fee_position_volume'] = $fee_position_volume;
            $this->newOpt = $o;
            return  [
                'fill_price' => $fill_price, //补仓价格
                'fill_volume' => $fill_volume, //补仓数量
            ];
        }
        return [];
    }

    public function getData()
    {
        return [
            'fill_data' => $this->getFillData(),
            'old_opt' => $this->opt,
            'new_opt' => $this->newOpt,
        ];
    }
}
